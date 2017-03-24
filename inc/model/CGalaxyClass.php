<?php

include_once(PATH_ROOT.'/inc/model/CUser.php');
include_once(PATH_ROOT.'/inc/CDbShell.php');

Class CGalaxyClass
{
	static protected $aDB = array();
    static public $session;

    public $sCreateTime;
	public $sModifiedTime;
	public $bStatus;

	protected $__iUserNo;
	protected $__oLastUser;

	public function __construct($multiData){
        if(!isset($multiData['user_no'])){
            $oCUser = self::$session->get('oCurrentUser');
            $multiData['user_no'] = isset($oCUser->iUserNo)?$oCUser->iUserNo:0;
        }
        $this->__iUserNo = $multiData['user_no'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    /*
        set & get LastUser data, which is an array from user DB
    */
    public function oLastUser(){
        if(is_null($this->__oLastUser)){
            $this->__oLastUser = CUser::oGetUser($this->__iUserNo);
        }
        return $this->__oLastUser;
    }

    public function sLocalCreateTime(){
        return date('Y-m-d H:i:s',strtotime('+8hour',strtotime($this->sCreateTime)));
    }

    public function sLocalModifiedTime(){
        return date('Y-m-d H:i:s',strtotime('+8hour',strtotime($this->sModifiedTime)));   
    }

    static public function oDB($sDBName){
        if(!isset(self::$aDB[$sDBName])){
            self::$aDB[$sDBName] = new CDbShell(    constant('_'.$sDBName.'_DB'),
                                                    constant('_'.$sDBName.'_HOST'),
                                                    constant('_'.$sDBName.'_USER'),
                                                    constant('_'.$sDBName.'_PASS')
                                                    );
        }
        return self::$aDB[$sDBName];
    }
}
?>