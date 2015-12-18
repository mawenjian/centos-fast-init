<?php

    class v2ex{
        public $once;
        public $user;
        public $password;
        public $session;
        public $CookiePath;
  
        //初始化变量
        public function __construct($user,$password){
            if(!preg_match('/^\w+$/',$user.$password)) exit('请输入合法用户名及密码');
            $this->user = $user;
            $this->password = $password;
            $this->CookiePath = dirname($_SERVER['SCRIPT_FILENAME']).'/cookie_'.$this->user.'.cookie';
  
        }
  
        //如果cookie文件的创建时间已经超过12小时，则自动删除
        public function update(){
            $ctime = filectime($this->CookiePath);
            if(($ctime + 12*60*60) < time()) @unlink($this->CookiePath);
        }
  
        //获取初始状态的Once值SESSIONID
        public function getOnceAndSession($url){
            $preg_once = '/<input\stype="hidden"\svalue="(\d+)"\sname="once"/';
            $preg_session = '/PB3_SESSION="(.*?)";/';
            $session = curl_init($url);
            curl_setopt($session,CURLOPT_HEADER,1);
            curl_setopt($session,CURLOPT_RETURNTRANSFER,1);
            $str = curl_exec($session);
            $result = preg_match($preg_session,$str,$sessionArr);
            if($result){
                preg_match($preg_once,$str,$onceArr);
                $this->session = $sessionArr[1];
                $this->once = $onceArr[1];
                curl_close($session);
            }
  
        }
  
        //模拟header头，执行登陆并更新cookie文件
        public function login(){
            $url = 'http://www.v2ex.com/signin';
            $post = "u={$this->user}&p={$this->password}&once={$this->once}&next=%2F";
            $header = array(
                'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: http://www.v2ex.com',
                'Referer: http://www.v2ex.com/signin'
            );
            $login = curl_init($url);
            curl_setopt($login,CURLOPT_HTTPHEADER,$header);
            curl_setopt($login,CURLOPT_HEADER,1);
            curl_setopt($login,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($login,CURLOPT_POST,1);
            curl_setopt($login,CURLOPT_POSTFIELDS,$post);
            curl_setopt($login,CURLOPT_COOKIE,'PB3_SESSION="'.$this->session.'"');
            curl_setopt($login,CURLOPT_COOKIEJAR,$this->CookiePath);
            curl_exec($login);
            curl_close($login);
        }
  
        //更新登陆成功状态下的once值，以用于签到
        public function getOnce($url,$CookiePath){
            $once = curl_init($url);
            curl_setopt($once,CURLOPT_HEADER,1);
            curl_setopt($once,CURLOPT_COOKIEFILE,$CookiePath);
            curl_setopt($once,CURLOPT_COOKIEJAR,$CookiePath);
            curl_setopt($once,CURLOPT_RETURNTRANSFER,1);
            $str = curl_exec($once);
            $preg = '/once=(\d+)/';
            curl_close($once);
            preg_match($preg,$str,$onceArr);
            $this->once = $onceArr[1];
        }
  
        //模拟header头，携带上cookie以及once值进行两个GET操作。
        public function sign($url,$path){
            $sign = curl_init($url.'?once='.$this->once);
            $header = array(
                'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Referer: http://www.v2ex.com/mission/daily'
            );
            curl_setopt($sign,CURLOPT_HEADER,1);
            curl_setopt($sign,CURLOPT_HTTPHEADER,$header);
            curl_setopt($sign,CURLOPT_COOKIEFILE,$path);
            curl_setopt($sign,CURLOPT_RETURNTRANSFER,0);
            curl_exec($sign);
            curl_setopt($sign,CURLOPT_URL, 'http://www.v2ex.com/mission/daily');
            curl_exec($sign);
            curl_close($sign);
        }
    }

    $loginUrl = 'http://www.v2ex.com/signin';          //登陆入口
    if($_GET['u'] == 'mawenjian') {
        $v2ex = new v2ex( 'mawenjian', 'passwd');
    } else if($_GET['u'] == 'smartma') {
        $v2ex = new v2ex( 'smartma', 'passwd');
    } else {
        exit('请输入合法用户名及密码');
    }
    //$v2ex = new v2ex( $username, $password);                  //你的账号密码，请符合v2ex的用户名规范
    if(!is_file($v2ex->CookiePath)){                 //对已有登陆状态cookie的账号，程序会自动跳过登陆
        @file_put_contents($v2ex->CookiePath,'');
        $v2ex->getOnceAndSession($loginUrl);         //获取初始状态的once值以及SESSIONID  
        $v2ex->login();
    }
    $v2ex->getOnce('http://www.v2ex.com',$v2ex->CookiePath); //获得最新Once值，用于签到
    $v2ex->sign('http://www.v2ex.com/mission/daily/redeem',$v2ex->CookiePath); //执行签到步骤
    $v2ex->update();
?>