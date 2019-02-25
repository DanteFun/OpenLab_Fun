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
            if($this->CreateTable($Table_Name,$Field_Name,0))
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

    public function ItemsInTable($Item,$Table_Name,$Condition_Name,$Condition_Value,$Is_Distinct)
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
        if($Is_Distinct == 1)
        {
            $Sql = 'select distinct '.$Items.' from '.$Table_Name;
        }
        else
        {
            $Sql = 'select '.$Items.' from '.$Table_Name;
        }
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
            if($Field_Name[$i] == '仪器个数')
            {
                $Result_Arr[$i] = 'int';
            }
            $i++;
        }
        $Result_Arr[$i] = $Field_Name[$i] == '密码' ? 'varchar(20)' : 'varchar(600)';
        return $Result_Arr;
    }

    public function CreateTable($Table_Name,$Field_Name,$IS_PK)
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
            $this->CreateTable($table_name,$table_content[1],$IS_PK);
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
}