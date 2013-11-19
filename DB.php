<?php
include_once 'DB.config.php';
if(class_exists('DB') != true){
class DB{
	
	private static $conn;
	private static $connect;
	
	public static function Base($B,$T){
	    self::$conn['base']=$B;
		self::$conn['table']=$T;
		self::connMain();
		self::$connect=self::_connect();
		date_default_timezone_set ('Etc/GMT-8');
		
	}
	
	private function connMain(){
		self::$conn['host']= DBHOST;
		self::$conn['user']= DBUSER;
		self::$conn['paw']= DBPW;
	}
	
	private static function _connect(){
		$con=mysql_connect(self::$conn['host'],self::$conn['user'],self::$conn['paw']);
		if(@$con){
			mysql_select_db(self::$conn['base'], $con);
			mysql_query("set names ".DBCHARSET);
			return $con;
		}else{
			return false;
		}
	}
	
	public static function SET($Table,$Where="",$DES="",$LIMIT=""){
		$i=0;
		if(self::$connect){
			if(!empty($Where)){
				$W="WHERE $Where";
			}else{
				$W="";
			}
			if(!empty($DES)){
				$D="ORDER BY $DES";
			}else{
				$D="";
			}
			if(!empty($LIMIT)){
				$L="LIMIT $LIMIT";
			}else{
				$L="";
			}
			if(self::$conn['table']){
				$T=self::$conn['table'].$Table;
		    }else{
				$T=$Table;
			}
		    @$data=mysql_query("SELECT * FROM $T $W $D $L",self::$connect);
			while(@$DB_ar=mysql_fetch_array($data,MYSQL_ASSOC)){
				$_DB[$i]=$DB_ar;
				$i++;
			}
			return @$_DB;
		}else{
			return false;
		}
	}
	
	public static function COUNT($_C,$_T,$_W){
		if(self::$connect){
			if(self::$conn['table']){
				$_T=self::$conn['table'].$_T;
		    }else{
				$_T=$_T;
			}
			if(!empty($_W)){
				$_W="WHERE $_W";
			}else{
				$_W="";
			}
			@$data=mysql_query("SELECT $_C FROM $_T $_W",self::$connect);
			return @mysql_fetch_array($data,MYSQL_ASSOC);
		}
	}
	
	public static function IN($T,$P,$V){
		if(self::$connect){
			if(self::$conn['table']){
				$T=self::$conn['table'].$T;
		    }
		    return mysql_query("INSERT INTO $T ($P) VALUES ($V)",self::$connect);
		}
	}
	
	public static function UP($TB,$UP,$WH){
		if(self::$connect){
			if(self::$conn['table']){
				$TB=self::$conn['table'].$TB;
		    }
		    return mysql_query("UPDATE $TB SET $UP WHERE $WH");
		}
	}
	
	public static function DEL($TB,$WH){
		if(self::$connect){
			if(self::$conn['table']){
				$TB=self::$conn['table'].$TB;
		    }
		    return mysql_query("DELETE FROM $TB WHERE $WH");
		}
	}
}
}
?>