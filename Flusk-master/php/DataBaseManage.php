<?php
/**
 * Created by PhpStorm.
 * User: Fun
 * Date: 2019/3/24
 * Time: 11:06
 */

class DataBaseManage
{
    private $host;
    private $username;              //MYSQL用户名
    private $password;              //MYSQL密码
    private $connObject;            //MYSQL连接标志
    private $result;                //Sql语句查询结果
    private $coding;                //MYSQL编码设置
    public  $resultRecord;          //当前语句执行结果
    private $Database_name;         //当前语句执行结果

    public function __construct($host,$username,$password,$Database_name,$coding)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->Database_name = $Database_name;
        $this->coding = $coding;
        $this->connect();
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function test()
    {
        return 1;
        echo '语句';
    }

    public function connect()
    {
        $this->connObject = mysqli_connect($this->host,$this->username,$this->password);
        if($this->connObject->connect_error)
        {
            $this->resultRecord = '数据库连接失败';
            return false;
        }
        else
        {
            $sql = "use ".$this->Database_name.";";
            if(mysqli_query($this->connObject,$sql))
            {
                $this->resultRecord = $this->Database_name.'连接成功';
                return true;
            }
        }
    }

    public function disconnect()
    {
        if(mysqli_close($this->connObject))
        {
            $this->resultRecord = $this->Database_name.'关闭成功';
            return true;
        }
        else
        {
            $this->resultRecord = $this->Database_name.'关闭失败';
            return false;
        }
    }

    public function insert($Table_Name,$Field_Name,$Field_Value)
    {
        $i = 0;
        $Field_Number = count($Field_Value);
        $Value = '';
        while($i < $Field_Number)
        {
            $Value.= '"'.$Field_Value[$i].'"';
            $Value.= $i == $Field_Number-1 ? '':',';
            $i++;
        }
        if($this->Table_Existed($Table_Name))
        {
            $Sql = 'insert into '.$Table_Name.' values('.$Value.');';
            if(mysqli_query($this->connObject,$Sql))
            {
                $this->resultRecord = $Table_Name.' 更新成功';
                return true;
            }
            else
            {
                $this->resultRecord = $Table_Name.' 更新失败';
                return false;
            }
        }
        else
        {
            if($this->New_CreateTable($Table_Name,$Field_Name,0))
            {
                $Sql = 'insert into '.$Table_Name.' values('.$Value.');';
                if(mysqli_query($this->connObject,$Sql))
                {
                    $this->resultRecord = $Table_Name.' 更新成功';
                    return true;
                }
                else
                {
                    $this->resultRecord = $Table_Name.' 更新失败';
                    return false;
                }
            }
        }
    }

    public function ItemsInTable($Item,$Table_Name,$Condition_Name,$Condition_Value)
    {
        $i = 0;
        $row = 0;
        $Items = '';
        $Condition = '';
        $Result_Arr = array();
        $Item_Number = count($Item);
        $Condition_Number = count($Condition_Name);
        while ($i < $Item_Number)
        {
            $Items.= $Item[$i];
            $Items.= $i == $Item_Number - 1 ? '':',';
            $i++;
        }
        $i = 0;
        while ($i < $Condition_Number)
        {
            $Condition.=$Condition_Name[$i].' = "'.$Condition_Value[$i].'"';
            $Condition.= $i == $Condition_Number - 1 ? ';':' and ';
            $i++;
        }
        $Sql = 'select '.$Items.' from '.$Table_Name;
        $Sql.= count($Condition_Name) == 0 ? '; ': ' where '.$Condition;
        $this->result = mysqli_query($this->connObject,$Sql);
        if($this->result == false)
        {
            return -1;
        }
        else
        {
            $row_count = mysqli_num_rows($this->result);
            if($row_count == 0)
            {
                return 1;
            }
            while($ass_arr = mysqli_fetch_assoc($this->result))
            {
                for($j = 0;$j < count($Item);$j++)
                {
                    $Result_Arr[$row][$j] = $ass_arr[$Item[$j]];
                }
                $row++;
            }
            return $Result_Arr;
        }
    }


    public function selectAllTable()
    {
        $result_arr = array();
        $sql = "show tables;";
        $this->result = mysqli_query($this->connObject,$sql);
        while($ass_arr = mysqli_fetch_assoc($this->result))
        {
            $result_arr[] = $ass_arr["Tables_in_openlab"];
        }
        return $result_arr;
    }


    public function selectAllField($Table_Name)
    {
        $result_field = array();
        $sql = "show fields from ".$Table_Name.";";
        if($this->result = mysqli_query($this->connObject,$sql))
        {
            while($ass_arr = mysqli_fetch_assoc($this->result))
            {
                $result_field[] = $ass_arr["Field"];
            }
            $this->resultRecord = $Table_Name.' 字段获取成功！';
            return $result_field;
        }
        else
        {
            $this->resultRecord = $Table_Name.' 字段获取失败！';
            return -1;
        }
    }


    public function Table_Existed($Table_Name)
    {
        $result_arr = $this->selectAllTable();
        if(count($result_arr) == 0)
        {
            return false;
        }
        for($i = 0;$i < count($result_arr);$i++)
        {
            if($Table_Name == $result_arr[$i])
            {
                return true;
            }
        }
        return false;
    }


    public function Field_Type($Field_Name)
    {
        $i = 0;
        $Field_Count = count($Field_Name);
        $Result_Arr = array();
        while($i < $Field_Count-1)
        {
            $Result_Arr[$i] = 'varchar(20)';
            $i++;
        }
        $Result_Arr[$i] = $Field_Name[$i] == '密码' ? 'varchar(20)' : 'varchar(600)';
        return $Result_Arr;
    }

    public function New_CreateTable($Table_Name,$Field_Name,$IS_PK)
    {
        $Field_Type = $this->Field_Type($Field_Name);
        $sql = 'create table '.$Table_Name.'(';
        for($i = 0;$i < count($Field_Name);$i++)
        {
            if(($i == 0)&&$IS_PK)
            {
                $sql.=$Field_Name[$i].' '.$Field_Type[$i].' primary key';
            }
            elseif (($i == 0)&&!$IS_PK)
            {
                $sql.=$Field_Name[$i].' '.$Field_Type[$i].' ';
            }
            elseif(($i != 0)&&!$IS_PK)
            {
                $sql.=','.$Field_Name[$i].' '.$Field_Type[$i].' ';
            }
            else
            {
                $sql.=','.$Field_Name[$i].' '.$Field_Type[$i].' not null';
            }
        }
        $sql.=');';
        echo $sql;
        if(mysqli_query($this->connObject,$sql))
        {
            $this->resultRecord = $Table_Name.' 创建成功！';
            return true;
        }
        else
        {
            $this->resultRecord = $Table_Name.' 创建失败！';
            return false;
        }
    }

    public function update($Table_Name,$Field_Name,$Field_Value,$Condition,$Condition_Value)
    {
        $i = 0;
        $Sql = '';
        $temp = '';
        while($i < count($Condition_Value))
        {
            $temp.= 'update '.$Table_Name.' set '.$Field_Name.' = "'.$Field_Value[$i].'" where '.$Condition.' = "'.$Condition_Value[$i].'";';
            $Sql.= $temp;
            $i++;
        }
        if(mysqli_multi_query($this->connObject,$Sql))
        {
            $this->resultRecord = $Table_Name.' 更新成功！';
            return true;
        }
        else
        {
            $this->resultRecord = $Table_Name.' 更新失败！';
            return false;
        }
    }

    public function Delete($table_name,$Condition,$Condition_Value)
    {
        $i = 0;
        $items = '';
        $Condition_Number = count($Condition);
        while ($i < $Condition_Number)
        {
            $items.=$Condition[$i].' = "'.$Condition_Value[$i].'"';
            $items.= $i == $Condition_Number - 1 ? ';':' and ';
            $i++;
        }
        $Sql = 'delete from '.$table_name.' where '.$items;
        echo $Sql;
        if(mysqli_query($this->connObject,$Sql))
        {
            $this->resultRecord = $Condition[0].' 删除成功！';
            return true;
        }
        else
        {
            $this->resultRecord = $Condition[0].' 删除失败！';
            return false;
        }
    }


    public function droptable($tablename)
    {
        $sql ="drop table " . $tablename.";";
        if(mysqli_query($this->connObject,$sql))
        {
            $this->resultRecord = $tablename.'删除成功！';
            return true;
        }
        else
        {
            $this->resultRecord = $tablename.'删除失败！';
            return false;
        }
    }

    public function ImportExcel($table_name,$table_content,$table_length)
    {
        $sql = '';
        if($this->Table_Existed($table_name))
        {
            return -1;
        }
        else
        {
            switch($table_name)
            {
                case '技术动态':
                case '通知通告':
                case '下发实验':
                case '已预约实验':
                    $IS_PK = 0;
                    break;
                default:
                    $IS_PK = 1;
                    break;
            }
            $this->New_CreateTable($table_name,$table_content[1],$IS_PK);
            for($index = 2;$index < count($table_content);$index++)
            {
                $temp = "INSERT INTO ".$table_name." VALUES"."(";
                for($indexJ = 0;$indexJ < $table_length;$indexJ++)
                {
                    $temp.= '"'.$table_content[$index][$indexJ];
                    $temp.= $indexJ == $table_length - 1 ? '");' : '",';
                }
                $sql.=$temp;
            }
            echo $sql;
            if(mysqli_multi_query($this->connObject,$sql))
            {
                $this->resultRecord = $table_name.'导入成功！';
                return true;
            }
            else
            {
                $this->resultRecord = $table_name.'导入失败！';
                return false;
            }
        }
    }


//    public function ExcelToTable($table_name,$table_content,$table_rowlen)
//    {
//        $sql = "";
//        if(!$this->Table_Existed($table_name))
//        {
//            $this->New_CreateTable($table_name,$table_content[1],$table_rowlen);
//        }
//
//
//        for($index = 2;$index<count($table_content);$index++)
//        {
//            $temp = "INSERT INTO ".$table_name." VALUES"."( ";
//            for($indexJ = 0;$indexJ<$table_rowlen;$indexJ++)
//            {
//                if($indexJ == $table_rowlen-1)
//                {
//                    if($table_name == '老师'|| $table_name == '学生')
//                    {
//                        $temp.="'".$table_content[$index][$indexJ]."'".",";
//                        $temp.="123456);";
//                    }
//                    else
//                    {
//                        $temp.="'".$table_content[$index][$indexJ]."'".");";
//                    }
//                }
//                else
//                {
//                    $temp.="'".$table_content[$index][$indexJ]."'".",";
//                }
//            }
//            $sql.=$temp;
//        }
//        return mysqli_multi_query($this->connObject,$sql);
//    }

//    public function Login($ID_Name,$Password,$IsTeacher)
//    {
//        if($IsTeacher)
//        {
//            $return_SQL = "select 教师姓名,密码 from 老师 where 教师姓名 = "."'".$ID_Name."'".";";
//            $this->result = mysqli_query($this->connObject,$return_SQL);
//            $this->row = mysqli_num_rows($this->result);
//            if($this->row == 0)
//            {
//                return -1;
//            }
//            else
//            {
//                while($row = $this->result->fetch_assoc())
//                {
//                    if($row["密码"] == $Password)
//                    {
//                        return 1;
//                    }
//                    else
//                    {
//                        return 0;
//                    }
//                }
//            }
//        }
//        else
//        {
//            $return_SQL = "select 姓名,密码 from 学生 where 姓名 = "."'".$ID_Name."'".";";
//            $this->result = mysqli_query($this->connObject,$return_SQL);
//            $this->row = mysqli_num_rows($this->result);
//            if($this->row == 0)
//            {
//                return -1;
//            }
//            else
//            {
//                while($row = $this->result->fetch_assoc())
//                {
//                    if($row["密码"] == $Password)
//                    {
//                        return 2;
//                    }
//                    else
//                    {
//                        return 0;
//                    }
//                }
//            }
//        }
//    }


//    public function Get_Major($student_name)
//    {
//        $result_arr = '';
//        $test_name = array();
//        $lab_address = array();
//        $teacher_name = array();
//        $sql = 'select 班级 from 学生 where 姓名 = "'.$student_name.'";';
//        $this->result=mysqli_query($this->connObject,$sql);
//        while($ass_arr = mysqli_fetch_assoc($this->result))
//        {
//            $result_arr = $ass_arr["班级"];
//        }
//        $Major = substr($result_arr,0,6);
//        $sql = 'select * from 下发实验 where 实验系别 = "'.$Major.'";';
//        $this->result=mysqli_query($this->connObject,$sql);
//        while($ass_arr = mysqli_fetch_assoc($this->result))
//        {
//            $test_name[] = $ass_arr["实验名称"];
//            $lab_address[] = $ass_arr["实验室地址"];
//            $teacher_name[] = $ass_arr["教师名称"];
//        }
//        $result = [$test_name,$lab_address,$teacher_name,$Major];
//        return $result;
//    }

//    public function get_test($item,$teacher_name)
//    {
//        $Major = "";
//        if($item == 'test')
//        {
//            $sql = "select 实验号,实验名称,实验系别 from 实验 where 实验系别 = (select 教师系别 from 老师 where 教师姓名 = '".$teacher_name."');";
//        }
//        else
//        {
//            $sql = "select 仪器名,仪器个数 from 实验仪器 where 仪器系别 = (select 教师系别 from 老师 where 教师姓名 = '".$teacher_name."');";
//        }
//        $this->result=mysqli_query($this->connObject,$sql);
//        $result_arr = array();
//        while($ass_arr = mysqli_fetch_assoc($this->result))
//        {
//            if($item == 'test')
//            {
//                $result_arr[0][] = $ass_arr["实验号"];
//                $result_arr[1][] = $ass_arr["实验名称"];
//                $Major = $ass_arr["实验系别"];
//            }
//            else
//            {
//                $result_arr[0][] = $ass_arr["仪器名"];
//                $result_arr[1][] = $ass_arr["仪器个数"];
//            }
//        }
//        $result_arr[2] = $Major;
//        return $result_arr;
//    }



//    public function All_testroom()
//    {
//        $sql = "select 使用中,实验室地址 from 实验室;";
//        $this->result=mysqli_query($this->connObject,$sql);
//        $result_arr = array();
//        while($ass_arr = mysqli_fetch_assoc($this->result))
//        {
//            if($ass_arr["使用中"] == 0)
//            {
//                $result_arr[] = $ass_arr["实验室地址"];
//            }
//        }
//        return $result_arr;
//    }

//    public function delete_content($sql)
//    {
//        return $this->result = mysqli_query($this->connObject,$sql);
//    }


//    public function createDatabase($databasename)
//    {
//        $sql ='create database ' . $databasename.";";
//        if(mysqli_query($this->connObject,$sql))
//        {
//            return true;
//        }
//        else
//        {
//            return false;
//        }
//    }
//
//    public function dropDatabase($databasename)
//    {
//        $sql ="drop database ". $databasename.";";
//        if(mysqli_query($this->connObject,$sql))
//        {
//            echo "drop database success!";
//        }
//        else
//        {
//            echo "drop database failure!";
//        }
//    }
//


//    public function update_lab($address,$value,$device_name,$device_num)
//    {
//        $sql = "update 实验室 set 使用中 = $value where 实验室地址='".$address."';";
//        $this->result=mysqli_query($this->connObject,$sql);
//        if($device_name!=''&&$device_num!='')
//        {
//            for($i = 0;$i<count($device_name);$i++)
//            {
//                $sql = "update 实验仪器 set 仪器个数 = $device_num[$i] where 仪器名='".$device_name[$i]."';";
//                $this->result=mysqli_query($this->connObject,$sql);
//            }
//        }
//        return $this->result;
//    }

//    public function Insert_Table($sql)
//    {
//        $this->result=mysqli_query($this->connObject,$sql);
//        return $this->result;
//    }

//    public function SubScribedRoom($subscribe_date,$subscribe_time)
//    {
//        $result = 0;
//        $sql = 'select * from 已预约实验';
//        $this->result=mysqli_query($this->connObject,$sql);
//        while($ass_arr = mysqli_fetch_assoc($this->result))
//        {
//            if($subscribe_date == ($ass_arr["实验日期"]) &&$subscribe_time == ($ass_arr["实验课时"]))
//            {
//                $result = 1;
//            }
//        }
//        return $result;
//    }

//    public function SQLDataType($SQLData)
//    {
//        $type = gettype($SQLData);
//        $return_SQL = "";
//        switch ($type)
//        {
//            case 'integer':
//                $return_SQL = " INT(10)";
//                break;
//            case 'float':
//                $return_SQL = " FLOAT(4)";
//                break;
//            case 'string':
//                $return_SQL = " VARCHAR(20)";
//                break;
//            default:
//                break;
//        }
//        if($SQLData=="性别")
//        {
//            $return_SQL = " VARCHAR(4)";
//        }
//        return $return_SQL;
//    }



//    public function CreateTable($table_name,$content_First,$table_rowlen)
//    {
//        $sql = "";
//        for($index=0;$index<$table_rowlen;$index++)
//        {
//            if($index==0)
//            {
//                if($table_name == '已预约实验')
//                {
//                    $sql = "create table ".$table_name."(".$content_First[$index]." ".$this->SQLDataType($content_First[$index]).' NOT NULL,';
//
//                }
//                else
//                {
//                    $sql = "create table ".$table_name."(".$content_First[$index]." ".$this->SQLDataType($content_First[$index])." PRIMARY KEY,";
//                }
//            }
//            else if(($table_name != '老师')&&($table_name != '学生')&&$index==$table_rowlen-1)
//            {
//                $sql.= $content_First[$index]." varchar(600));";
//            }
//            else
//            {
//                $sql.= $content_First[$index]." ".$this->SQLDataType($content_First[$index]).",";
//            }
//        }
//
//        if($table_name == '老师'|| $table_name == '学生')
//        {
//            $sql.= "密码  VARCHAR(20) NOT NULL);";
//        }
//        echo $sql;
//        if(mysqli_query($this->connObject,$sql))
//        {
//            return true;
//        }
//        else
//        {
//            return false;
//        }
//    }



//    public function selectPartContent($table_name,$Field,$condition_name,$condition)
//    {
//        $result_arr = array();
//        $var = 0;
//        $sql = "select * from  $table_name where $condition_name = '$condition';";
//        $this->result=mysqli_query($this->connObject,$sql);
//        while($ass_arr = mysqli_fetch_assoc($this->result))
//        {
//            for($j = 0;$j < count($Field);$j++)
//            {
//                $result_arr[$var][$j] = $ass_arr[$Field[$j]];
//            }
//            $var++;
//        }
//        return $result_arr;
//    }
//
//    public function selectAllContent($table_name,$Field)
//    {
//        $result_arr = array();
//        $result_temp = array();
//        $var = 0;
//        for($i = 0;$i < count($table_name);$i++)
//        {
//            $sql = "select * from  $table_name[$i]";
//            $this->result=mysqli_query($this->connObject,$sql);
//            $this->row = mysqli_num_rows($this->result);
//            while($ass_arr = mysqli_fetch_assoc($this->result))
//            {
//                for($j = 0;$j < count($Field[$i]);$j++)
//                {
//                    $result_temp[$var][$j] = $ass_arr[$Field[$i][$j]];
//                }
//                $var++;
//            }
//            $var = 0;
//            $result_arr[$i] = $result_temp;
//            $result_temp=array();
//        }
//        return $result_arr;
//    }
//
//    public function selectAll($result_arr)
//    {
//        $result_field = array();
//        for($i = 0;$i < count($result_arr);$i++)
//        {
//            $sql = "show fields from ".$result_arr[$i].";";
//            $this->result=mysqli_query($this->connObject,$sql);
//            $this->row = mysqli_num_rows($this->result);
//            while($ass_arr = mysqli_fetch_assoc($this->result))
//            {
//                $result_field[$i][] = $ass_arr["Field"];
//            }
//        }
//        return $result_field;
//    }
}