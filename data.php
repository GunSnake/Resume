<?php

$resume = new Resume();
echo $resume->dealResume($_REQUEST);

class Resume{

    /**
     * 启用本地
     * @var int
     */
    private $local = 1;

    /**
     * 动作
     * @var string
     */
    private $act = 'show';

    /**
     * 是否出错
     * @var int
     */
    private $errno = 0;

    /**
     * 是否显示
     * @var int
     */
    private $show = 1;

    /**
     * 是否可编辑
     * @var int
     */
    private $edit = 0;

    /**
     * 错误信息
     * @var string
     */
    private $mess = '';

    /**
     * 查看密码
     * @var string
     */
    private $viewpass = '';// 密码必须大于3位。留空则任何人可以访问

    /**
     * 修改密码
     * @var string
     */
    private $adminpass = 'confirm';

    /**
     * 标题
     * @var string
     */
    private $title = '';

    /**
     * 副标题
     * @var string
     */
    private $subtitle = '';

    /**
     * 内容
     * @var string
     */
    private $content = '';


    /**
     * Resume constructor.
     * @param $title
     * @param $subtitle
     */
    public function __construct()
    {
        $data = $this->manegeText('read');
        if (!$data){
            $this->title = 'XX';
            $this->subtitle = 'XXX开发工程师';
            $this->manegeText('write');
        }else{
            $data_unjson = json_decode($data, true);
            list($this->title, $this->subtitle) = [$data_unjson[0], $data_unjson[1]];
        }
    }

    /**
     * 修改title文件
     * @param $type
     * @return bool|string
     */
    private function manegeText($type)
    {
        if ($type == 'read') return file_get_contents('data.txt');
        if ($this->title && $this->subtitle)
            file_put_contents('data.txt',json_encode([$this->title, $this->subtitle]));
    }

    /**
     * 处理分发
     * @param $request
     * @return mixed
     */
    public function dealResume($request)
    {
        $this->act = $request['a'] ? $request['a'] : $this->act;
        $method = 're_'.$this->act;
        return $this->$method($request);
    }

    /**
     * 展示简历
     * @param $request
     * @return string
     */
    private function re_show($request)
    {
        $vpass = isset($request['vpass']) ? $request['vpass'] : '';
        if( strlen( $this->viewpass ) > 0 && trim($vpass) != $this->viewpass ){
            $this->show = 0;
            $this->title = '';
            $this->subtitle = '';
            $this->setContent('', true);
            return $this->sendErr('需要密码');
        }
        $this->setContent();
        return $this->sendRes();
    }

    /**
     * 更新数据
     * @param $request
     * @return string
     */
    private function re_update($request)
    {
        $epass = $request['admin_password'];
        if( strlen( $this->adminpass ) > 0 && trim($epass) != $this->adminpass )
            return $this->sendErr('密码错误不能修改');
        if ($request['title']) $this->title = $request['title'];
        if ($request['subtitle']) $this->subtitle = $request['subtitle'];
        # 写入新title
        $this->manegeText('write');
        # 写入新内容
        $this->setContent($request['content']);

        return $this->storeContent();
    }

    /**
     * 存储修改后的简历
     */
    private function storeContent()
    {
        if (!$this->content) return $this->sendErr('没有内容');
        file_put_contents('README.md', $this->content);
        $this->mess = '修改成功';
        return $this->sendRes();
    }

    /**
     * 设置简历内容
     */
    private function setContent($content = '', $force_empty = false)
    {
        $this->content = $content ? $content :file_get_contents('README.md');
        if ($force_empty) $this->content = '';
    }

    /**
     * 发送错误信息
     */
    public function sendErr($mess){
        $this->errno = 1;
        $this->mess = $mess;
        return $this->sendRes();
    }

    /**
     * 发送数据
     */
    public function sendRes()
    {
        $data['local'] = $this->local;
        $data['errno'] = $this->errno;
        $data['show'] = $this->show;
        $data['mess'] = $this->mess;
        $data['edit'] = $this->edit;
        $data['title'] = $this->title;
        $data['subtitle'] = $this->subtitle;
        $data['content'] = $this->content;

        return json_encode($data);
    }

}