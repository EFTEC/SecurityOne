<?php

namespace eftec;

/**
 * Class SecurityOne
 * This class manages the security.
 * @version 1.3 20180922
 * @package eftec
 * @author Jorge Castro Castillo
 * @copyright (c) Jorge Castro C. MIT License  https://github.com/EFTEC/SecurityOne
 * @see https://github.com/EFTEC/SecurityOne
 */
class SecurityOne
{
    const NOTALLOWED=0;
    const READ=1;
    const WRITE=2;
    const READWRITE=SecurityOne::READ +SecurityOne::WRITE;
    const ADMIN=SecurityOne::READ +SecurityOne::WRITE + 4;


    public $user;
    public $password;
    public $name;

    public $cookieID;

    public $email=null;
    public $iduser=null;
    public $phone=null;
    public $address=null;
    public $uid;
    /** @var string[] */
    public $group;


    /** @var boolean */
    public $isLogged=false;

    public $encmethod='sha256';
    public $cryptpwd='123456'; // not yet used.
    public $salt='somesalt';


    /** @var callable */
    private  $validateFn;
    /** @var callable */
    private  $loginFn;
    /** @var callable */
    private  $isAllowedFn;
    /** @var callable */
    private  $getPermissionFn;
    /** @var callable */
    private  $storeCookieFn;
    /** @var callable */
    private  $getStoreCookieFn;
    /**
     * SecurityOne constructor.
     * @param bool $autologin
     * @param string $salt it's used for encryption, it must be changed and be unique for your project.
     */
    public function __construct($autologin=true,$salt="Zoamelgustar")
    {
        $this->salt=$salt;
        $this->isLogged=false;
        $this->validateFn=function(SecurityOne $sec) {
            return true;
        };
        $this->loginFn=function(SecurityOne $sec) {
            $sec->factoryUser('','','','',null,null,null,null);
            return true;
        };
        $this->isAllowedFn=function($who,$where="",$when="",$id="") {
            return true;
        };
        $this->getPermissionFn=function($who, $where="", $when="", $id="") {
            return SecurityOne::READWRITE;
        };

        @session_start();
        if ($autologin) {
            $this->isLogged=is_array($this->getCurrent());
        }
    }

    public function factoryUser($user,$password,$name,$group,$email=null,$iduser=null,$phone=null,$address=null) {
        $this->user=$user;
        $this->password=$password;
        $this->name=$name;
        $this->group=$group;
        $this->email=$email;
        $this->iduser=$iduser;
        $this->phone=$phone;
        $this->address=$address;
    }

    /**
     * It generates an unique UID between the current IP and the user agent.
     * @return string
     */
    private function genUID() {
        // HTTP_CLIENT_IP and HTTP_X_FORWARDE‌​D_FOR can be forged.
        // REMOTE_ADDR is the same for all clients connected to a proxy.
        $ip=@$_SERVER['HTTP_CLIENT_IP'].@$_SERVER['HTTP_X_FORWARDE‌​D_FOR'].@$_SERVER['REMOTE_ADDR'];
        // HTTP_USER_AGENT could be forge.
        $browser=@$_SERVER['HTTP_USER_AGENT'];
        return md5($ip.$browser);
    }
    protected function serialize() {
        $r=['user'=>$this->user
            ,'name'=>$this->name
            ,'uid'=>$this->uid
            ,'group'=>$this->group];
        /* optional fields */
        if ($this->email!==null) $r['email']=$this->email;
        if ($this->iduser!==null) $r['iduser']=$this->iduser;
        if ($this->phone!==null) $r['phone']=$this->phone;
        if ($this->address!==null) $r['address']=$this->address;

        return $r;
    }

    public function encrypt($password)
    {
        return hash($this->encmethod,$this->salt.$password);
    }


    private function deserialize($array) {
        $this->user=@$array['user'];
        $this->name=@$array['name'];
        $this->uid=@$array['uid'];
        $this->group=@$array['group'];
        /* optional fields */
        $this->email=@$array['email'];
        $this->iduser=@$array['iduser'];
        $this->phone=@$array['phone'];
        $this->address=@$array['address'];
    }


    /**
     * @param callable $fn Example: function(SecurityOne $sec) {return true;}
     */
    public function setValidateFn(callable $fn) {
        $this->validateFn=$fn;
    }

    /**
     * @param callable $fn Example: function(SecurityOne $sec) {return true;}
     */
    public function setLoginFn(callable $fn) {
        $this->loginFn=$fn;
    }

    /**
     * @param callable $fn Example: function(SecurityOne $who,$where="",$when="",$id="")  {return true;}
     */
    public function setIsAllowedFn(callable $fn) {
        $this->isAllowedFn=$fn;
    }

    /**
     * @param callable $fn Example: function(SecurityOne $who,$where="",$when="",$id="")  {return SecurityOne::ADMIN;}
     */
    public function setPermissionFn(callable $fn) {
        $this->getPermissionFn=$fn;
    }
    public function setStoreCookieFn(callable $fn) {
        $this->storeCookieFn=$fn;
    }
    public function setGetStoreCookieFn(callable $fn) {
        $this->getStoreCookieFn=$fn;
    }
    /**
     * Returns if the user is valid or not.
     */
    public function isValid() {
        call_user_func($this->validateFn,$this);
    }

    /**
     * Returns true if the user is member of a group.
     * @param $nameGroup
     * @return bool
     */
    public function isMember($nameGroup) {
        return (in_array($nameGroup,$this->group));
    }

    public function getStoreCookie() {
        if(isset($_COOKIE['phpcookiesess'])) {
            $this->cookieID=$_COOKIE['phpcookiesess'];
            return call_user_func($this->getStoreCookieFn,$this);
        }
        return false; // no cookie
    }

    // we store the cookie
    public function storeCookie() {
        @setcookie("phpcookiesess", $this->cookieID, time() + (86400 * 365), "/"); // 1 year
        return call_user_func($this->storeCookieFn,$this);
    }

    /**
     * It's used when the user log with an user and password. So it must be used only in the login screen.
     * After that, the user is stored in the session.
     * @param string $user
     * @param string $password Not encrypted password
     * @param bool $storeCookie
     * @param string $other
     * @return bool
     */
    public function login($user,$password,$storeCookie=false,$other='') {
        $this->user=$user;
        $this->password=$this->encrypt($password);
        $this->uid=$this->genUID();
        //$this->other=$other;
        if (call_user_func($this->loginFn,$this)) {
            $this->fixSession($storeCookie);
            return true;
        } else {
            @session_destroy();
            @session_write_close();
            $this->isLogged=false;
            return false;
        }
    }
    public function fixSession($storeCookie) {
        @$_SESSION['_user']=$this->serialize();
        if ($storeCookie) {
            $this->cookieID=sha1(uniqid().$this->genUID());
            $this->storeCookie();
        }
        @session_write_close();
        $this->isLogged=true;
    }
    /**
     * Logout and the session is destroyed.
     */
    public function logout() {
        $this->user="";
        $this->password="";
        $this->isLogged=false;
        unset($_COOKIE['phpcookiesess']);
        setcookie('phpcookiesess', null, -1, '/');
        @session_destroy();
        @session_write_close();
    }

    /**
     * Returns true if the user is logged. False if not. It also returns false if the UID doesn't correspond.
     * @return bool
     */
    public function isLogged() {
        if (!$this->isLogged) return false;
        if ($this->genUID()!=$this->uid) return false; // uid doesn't correspond.
        return true;
    }
    /**
     * Returns true if the user is allowed to do an operation $when on $where with $id.
     * @param string $where It could indicates a module, instance of type of object.
     * @param string $when  It could be an action or verb (edit,list,print)
     * @param string $id  It could be used to identify an element. Sometimes permissions are tied with a specific element.
     * @return boolean It could be used to identify an element. Sometimes permissions are tied with a specific element.
     */
    public function isAllowed($where="",$when="",$id="") {
        if ($this->genUID()!=$this->uid) return false; // uid doesn't correspond.
        // module1,edit,20
        // allowed to edit module1 when id 1
        return call_user_func($this->isAllowedFn,$this,$where,$when,$id);
    }

    /**
     * Returns a nivel of permission SecurityOne::NOTALLOWED|READ|WRITE|READWRITE|ADMIN
     * @param string $where It could indicates a module, instance of type of object.
     * @param string $when It could be an action or verb (edit,list,print)
     * @param string $id It could be used to identify an element. Sometimes permissions are tied with a specific element.
     * @return int SecurityOne::NOTALLOWED|READ|WRITE|READWRITE|ADMIN
     */
    public function getPermission($where="",$when="",$id="") {
        if ($this->genUID()!=$this->uid) return false; // uid doesn't correspond.
        // module1,edit,20
        // allowed to edit module1 when id 1
        return call_user_func($this->getPermissionFn,$this,$where,$when,$id);
    }

    /**
     * Load current user. It returns an array
     * @param bool $closeSession
     * @return bool
     */
    public function getCurrent($closeSession=false) {
        if (session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE) {
            $b=@session_start();
            if (!$b) return false; // session is not open and I am unable to open it
        }
        $obj=@$_SESSION['_user'];
        if ($obj!==null) $this->deserialize($obj);
        if ($closeSession) @session_write_close();

        return $obj;
    }

}