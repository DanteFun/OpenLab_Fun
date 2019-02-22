<?php
/**
 * Created by PhpStorm.
 * User: Fun
 * Date: 2019/2/17
 * Time: 11:08
 */
require_once 'DataBaseManage.php';
require_once 'C:\Users\Fun\PhpstormProjects\LabManage\vendor\gregwar\captcha\src\Gregwar\Captcha\CaptchaBuilder.php';

ini_set('max_execution_time',0);

$DBManage = new DataBaseManage('localhost','root','root','openlab','UTF-8');
$DBManage_1 = new DataBaseManage('localhost','root','root','openlab','UTF-8');
date_default_timezone_set('PRC');
$Operation = $_POST['Operation'];
switch ($Operation)
{
    case 'INSERT_DEPLOY':
        $i = 0;
        $Error = 0;
        $Device_Name = $_POST['device_name'];
        $Cost_Value = $_POST['cost_num'];
        $Device_Num = $_POST['device_num'];
        $Field_Name = ['实验名称','实验室地址','教师名称','实验系别'];
        $Field_Value = [$_POST['test_name'],$_POST['room_name'],$_POST['teacher_name'],$_POST['Major']];

        if($DBManage->insert('下发实验',$Field_Name,$Field_Value))
        {
            $Field_Value = ['1'];
            $Condition_Value = [$_POST['room_name']];
            if($DBManage->update('实验室','使用中',$Field_Value,'实验室地址',$Condition_Value))
            {
                if($DBManage->update('实验仪器','仪器个数',$Device_Num,'仪器名',$Device_Name))
                {
                    $Field_Name = ['实验名称','设备名称','设备使用个数'];
                    while($i < count($Cost_Value))
                    {
                        $Field_Value = [$_POST['test_name'],$Device_Name[$i],$Cost_Value[$i]];
                        $Error += $DBManage_1->insert('设备使用情况',$Field_Name,$Field_Value);
                        $i++;
                    }
                    if($Error == $i)
                    {
                        $Field_Name = ['日期','内容'];
                        $Field_Value = [date("Y-m-d-H-i-s"),$_POST['Major'].'系'.$_POST['teacher_name'].'老师发布了'.$_POST['test_name'].'实验,'.$_POST['Major'].'系的同学们快来预约啦~~'];
                        if($DBManage_1->insert('通知通告',$Field_Name,$Field_Value))
                        {
                            $Field_Value = ['实验名称','实验室地址','教师名称','实验系别'];
                            $Condition_Name = ['教师名称'];
                            $Condition_Value = [$_POST['teacher_name']];
                            $Result = $DBManage_1->ItemsInTable($Field_Value,'下发实验',$Condition_Name,$Condition_Value);
                            echo json_encode($Result);
                        }
                        else
                        {
                            echo $DBManage->resultRecord;
                        }
                    }
                    else
                    {
                        echo $DBManage->resultRecord;
                    }
                }
                else
                {
                    echo $DBManage->resultRecord;
                }
            }
            else
            {
                echo $DBManage->resultRecord;
            }
        }
        break;

    case 'INSERT_SUBSCRIBE':
        $Condition_Name = ['实验日期','实验课时'];
        $Condition_Value = [$_POST['subscribe_date'],$_POST['subscribe_time']];
        if($DBManage->ItemsInTable($Condition_Name,'已预约实验',$Condition_Name,$Condition_Value) == 1)
        {
            $Field_Name = ['实验名称','预约者','实验室地址','实验日期','实验课时','实验系别'];
            $Field_Value = [$_POST['test_name'],$_POST['student_name'],$_POST['room_name'],$_POST['subscribe_date'],$_POST['subscribe_time'],$_POST['major']];
            if($DBManage->insert('已预约实验',$Field_Name,$Field_Value))
            {
                echo '预约'.$_POST['test_name'].'成功！';
            }
            else
            {
                echo '预约'.$_POST['test_name'].'失败！';
            }
        }
        else
        {
            echo '该时间已被占用，请选择其他时间段！';
        }
        break;

    case 'IMPORT_EXCEL':
        $table_name = json_decode($_POST['table_name']);
        $table_content = json_decode($_POST['table_content']);
        $table_length = json_decode($_POST['table_length']);
        $DBManage->ImportExcel($table_name,$table_content,$table_length);
        echo $DBManage->resultRecord;
        break;

    case 'DELETE':
        $Condition_Name = [$_POST['table_field']];
        $Condition_Value = [$_POST['id']];
        $DBManage->Delete($_POST['table_name'],$Condition_Name,$Condition_Value);
        echo $DBManage->resultRecord;
        break;

    case 'GET_TEST':
        $item = ['教师系别'];
        $Condition_Name = ['姓名'];
        $Condition_Value = [$_POST['teacher_name']];
        $Major = $DBManage->ItemsInTable($item,'老师',$Condition_Name,$Condition_Value);
        $Major = implode("",$Major[0]);

        $item = ['实验号','实验名称','实验系别'];
        $Condition_Name = ['实验系别'];
        $Condition_Value = [$Major];
        $Major = $DBManage->ItemsInTable($item,'实验',$Condition_Name,$Condition_Value);
        echo json_encode($Major);
        break;

    case 'GET_DEVICE':
        $item = ['教师系别'];
        $Condition_Name = ['姓名'];
        $Condition_Value = [$_POST['teacher_name']];
        $Major = $DBManage->ItemsInTable($item,'老师',$Condition_Name,$Condition_Value);
        $Major = implode("",$Major[0]);

        $item = ['仪器名','仪器个数'];
        $Condition_Name = ['仪器系别'];
        $Condition_Value = [$Major];
        $Major = $DBManage->ItemsInTable($item,'实验仪器',$Condition_Name,$Condition_Value);
        echo json_encode($Major);
        break;

    case 'GET_TESTROOM':
        $item = ['实验室地址'];
        $Condition_Name = ['使用中'];
        $Condition_Value = ['0'];
        $Result = $DBManage->ItemsInTable($item,'实验室',$Condition_Name,$Condition_Value);
        echo json_encode($Result);
        break;

    case 'DROP_TABLE':
        $DBManage->droptable($_POST['select_Name']);
        echo $DBManage->resultRecord;
        break;

    case 'GET_DEPLOYED':
        $item = ['班级'];
        $Condition_Name = ['姓名'];
        $Condition_Value = [$_POST['student_name']];

        $Major = $DBManage->ItemsInTable($item,'学生',$Condition_Name,$Condition_Value);

        $Major = implode("",$Major[0]);
        $Major = substr($Major,0,6);

        $item = ['实验名称','实验室地址','教师名称'];
        $Condition_Name = ['实验系别'];
        $Condition_Value = [$Major];
        $Result = $DBManage->ItemsInTable($item,'下发实验',$Condition_Name,$Condition_Value);
        $Result[] = $Major;
        echo json_encode($Result);
        break;

    case 'GET_ALLDATABASE':
        $Result = array();
        $Return_Result = array();
        $i = 0;
        $Table_List = $DBManage->selectAllTable();
        $Condition_Name = [];
        $Condition_Value = [];
        while($i < count($Table_List))
        {
            $Field_List = $DBManage->selectAllField($Table_List[$i]);
            $Result[$i] = $DBManage->ItemsInTable($Field_List,$Table_List[$i],$Condition_Name,$Condition_Value);
            $Return_Result[$i] = [$Table_List[$i],$Field_List,$Result[$i]];
            $i++;
        }
        echo json_encode($Return_Result);
        break;

    case 'CAPTCHA':
        $builder = new \Gregwar\Captcha\CaptchaBuilder();
        $builder->build();
        session_start();
        $_SESSION['phrase'] = $builder->getPhrase();
        echo $builder->inline();
        break;

    case 'TEACHER_DEPLOYED':
        $Field_Value = ['实验名称','实验室地址','教师名称','实验系别'];
        $Condition_Name = ['教师名称'];
        $Condition_Value = [$_POST['teacher_name']];
        $Result = $DBManage->ItemsInTable($Field_Value,'下发实验',$Condition_Name,$Condition_Value);
        echo json_encode($Result);
        break;

    case 'GET_ALLTABLE':
        $Result = $DBManage->selectAllTable();
        echo json_encode($Result);
        break;

    case 'LOGIN':
        session_start();
        if($_POST['captcha'] != $_SESSION['phrase'])
        {
            echo '验证码错误！';
        }
        $Table_Name = $_POST['optradio'] ? '老师' : '学生';
        $item = ['姓名'];
        $Condition_Value = [$_POST['name']];
        if($DBManage->ItemsInTable($item,$Table_Name,$item,$Condition_Value) == 0)
        {
            echo '该用户不存在！';
        }
        else
        {
            $item = ['姓名','密码'];
            $Condition_Value = [$_POST['name'],$_POST['password']];
            if($DBManage->ItemsInTable($item,$Table_Name,$item,$Condition_Value) == 0)
            {
                echo '用户名或密码错误！';
            }
            else
            {
                echo $_POST['optradio'] ? '老师' : '学生';
            }
        }
        break;

    case 'INFO_SHARE':
        $Field_Value = ['日期','内容'];
        $Condition_Name = [];
        $Condition_Value = [];
        $Result = $DBManage->ItemsInTable($Field_Value,'通知通告',$Condition_Name,$Condition_Value);
        echo json_encode($Result);
        break;

    case 'TEC_SHARE':
        $Field_Name = ['日期','标题','类别','内容'];
        $Field_Value = [date("Y-m-d-H-i-s"),$_POST['content_title'],$_POST['choose_language'],$_POST['tec_content']];
        if($DBManage->insert('技术动态',$Field_Name,$Field_Value))
        {
            echo '技术动态下发成功！';
        }
        else
        {
            echo '技术动态下发失败！';
        }
        break;

    default:
        break;
}