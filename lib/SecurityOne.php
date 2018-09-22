<?php

namespace eftec;

/**
 * Class SecurityOne
 * This class manages the security.
 * @version 1.0 20180922
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
    public $name;
    public $email;
    public $uid;
    /** @var string[] */
    public $group;
    public $password;

    /** @var boolean */
    private $isLogged=false;


    /** @var callable */
    private  $validateFn;
    /** @var callable */
    private  $loginFn;
    /** @var callable */
    private  $isAllowedFn;
    /** @var callable */
    private  $getPermissionFn;

    /**
     * SecurityOne constructor.
     * @param bool $autologin
     */
    public function __construct($autologin=true)
    {
        $this->isLogged=false;
        $this->validateFn=function(SecurityOne $sec) {
            return true;
        };
        $this->loginFn=function(SecurityOne $sec) {
            $sec->user='';
            $sec->name='';
            $sec->email='';
            $sec->group='';
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
    private function serialize() {
        return ['user'=>$this->user
            ,'name'=>$this->name
            ,'email'=>$this->email
            ,'uid'=>$this->uid
            ,'group'=>$this->group];
    }
    private function deserialize($array) {
        $this->user=@$array['user'];
        $this->name=@$array['name'];
        $this->uid=@$array['uid'];
        $this->email=@$array['email'];
        $this->group=@$array['group'];
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

    /**
     * It's used when the user log with an user and password. So it must be used only in the login screen.
     * After that, the user is stored in the session.
     * @param string $user
     * @param string $password
     * @param string string $other
     * @return bool
     */
    public function login($user,$password,$other='') {
        $this->user=$user;
        $this->password=$password;
        $this->uid=$this->genUID();
        //$this->other=$other;
        if (call_user_func($this->loginFn,$this)) {
            @$_SESSION['_user']=$this->serialize();
            @session_write_close();
            $this->isLogged=true;
            return true;
        } else {
            @session_destroy();
            @session_write_close();
            $this->isLogged=false;
            return false;
        }
    }
    /**
     * Logout and the session is destroyed.
     */
    public function logout() {
        $this->user="";
        $this->password="";
        $this->isLogged=false;
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