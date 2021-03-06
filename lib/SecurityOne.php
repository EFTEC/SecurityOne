<?php

namespace eftec;

/**
 * Class SecurityOne
 * This class manages the security.
 * @version 2.1 2018-oct-27
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

    /** @var mixed id of the user. For example, it could be the identity of the database */
    public $iduser=null;
    /** @var string username (to log) of the user */
    public $user;
    /** @var string password of the user */
    public $password;
    /** @var string full name of the user */
    public $fullName;
    /** @var string email of the user. */
    public $email=null;
    /** @var int status of the user, 0=not enable */
    public $status;
    /** @var string Id of the cookie */
    public $cookieID;

    public $useCookie=true; // for store session

    /**
     * @var array fields of the user for example for the phone, address and such<br>
     * Example ['address'=>'','nationalid'=>'','phone'=>'','iddistrict'=>0];
     */
    public $extraFields=array();
    public $uid;
    /** @var string[] Group or permissions */
    public $group;
    public $role;


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
     * @param bool $autologin if true then it tries to load the user from the session (if any)
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
            $sec->factoryUser('','','',[],'',0,null,null,null);
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

    /**
     * It sets the current user.
     * @param string $user
     * @param string $password
     * @param string $name
     * @param string[] $group
     * @param string $role
     * @param int $status 0=disabled,1=enabled
     * @param string $email
     * @param string $iduser
     * @param array $extra
     */
    public function factoryUser($user,$password,$name,$group,$role,$status,$email=null,$iduser=null,$extra=[]) {
        $this->user=$user;
        $this->password=$password;
        $this->fullName=$name;
        $this->group=$group;
        $this->role=$role;
        $this->status=$status;
        $this->email=$email;
        $this->iduser=$iduser;
        $this->extraFields=$extra;
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

    /**
     * Returns an associative array with the current user
     * @return array=['user'='','name'=>'','uid'=>'','group'=>[],'role'=>''
     *              ,'email'=>'','iduser'=>'','extrafields'=>[]]
     */
    public function serialize() {
        $r=['user'=>$this->user
            ,'name'=>$this->fullName
            ,'uid'=>$this->uid
            ,'group'=>$this->group
            ,'role'=>$this->role];
        /* optional fields */
        if ($this->email!==null) $r['email']=$this->email;
        if ($this->iduser!==null) $r['iduser']=$this->iduser;

        $r['extrafields']=$this->extraFields;

        return $r;
    }

    /**
     * It encrypts a password. It uses the salt and algorytm defined by the class
     * @param $password (unencrypted password)
     * @return string (encrypted password)
     * @see SecurityOne::$salt
     * @see SecurityOne::$encmethod
     */
    public function encrypt($password)
    {
        return hash($this->encmethod,$this->salt.$password);
    }
    public function equalPassword($password) {
        return $this->password==$this->encrypt($password);
    }

    /**
     * Set the current user by using an associative array
     * @param $array=['user'=>'','name'=>'','uid'=>'','group'=>[],role=>''
     *          ,'status'=>0,'email'=>'','iduser'=>0,'extrafields'=>[]]
     */
    public function deserialize($array) {
        $this->user=@$array['user'];
        $this->fullName=@$array['name'];
        $this->uid=@$array['uid'];
        $this->group=@$array['group'];
        $this->role=@$array['role'];
        $this->status=@$array['status'];
        /* optional fields */
        $this->email=@$array['email'];
        $this->iduser=@$array['iduser'];
        $this->extraFields=@$array['extrafields'];
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
     * @return bool
     */
    public function login($user,$password,$storeCookie=false) {
        $this->user=$user;
        $this->password=$this->encrypt($password);
        $this->uid=$this->genUID();
        //$this->other=$other;
        if (call_user_func($this->loginFn,$this)) {
            $this->fixSession($storeCookie && $this->useCookie);
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
     * Logout and the session is destroyed. It doesn't redirect to the home page.
     */
    public function logout() {
        $this->user="";
        $this->password="";
        $this->isLogged=false;
        if ($this->useCookie) {
            unset($_COOKIE['phpcookiesess']);
            setcookie('phpcookiesess', null, -1, '/');
        }
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