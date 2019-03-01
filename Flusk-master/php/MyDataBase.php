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
        $Device_Num = $_POST['device_num'];
        $Remain_num = $_POST['remain_num'];
        $Field_Name = ['实验名称','实验室地址','教师名称','名称','仪器个数'];
        while($i < count($Device_Name))
        {
            $Field_Value = [$_POST['test_name'],$_POST['room_name'],$_POST['teacher_name'],$Device_Name[$i],$Device_Num[$i]];

            $Error += $DBManage->insert('下发实验',$Field_Name,$Field_Value);
            $i++;
        }
        if($Error == count($Device_Name))
        {
            $Field_Value = ['1'];
            $Condition_Value = [$_POST['room_name']];
            if($DBManage->update('实验室','使用中',$Field_Value,'实验室地址',$Condition_Value))
            {
                if($DBManage->update('实验仪器','仪器个数',$Remain_num,'名称',$Device_Name))
                {
                    $Field_Name = ['日期','内容'];
                    $Field_Value = [date("Y-m-d-H-i-s"),$_POST['teacher_name'].'老师发布了'.$_POST['test_name'].'实验,同学们快来预约啦~~'];
                    if($DBManage_1->insert('通知通告',$Field_Name,$Field_Value))
                    {
                        echo 'deploy success';
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
        break;

    case 'INSERT_SUBSCRIBE':
        $Condition_Name = ['实验日期','实验课时'];
        $Condition_Value = [$_POST['subscribe_date'],$_POST['subscribe_time']];
        if($DBManage->ItemsInTable($Condition_Name,'已预约实验',$Condition_Name,$Condition_Value,0) == 1 ||$DBManage->ItemsInTable($Condition_Name,'已预约实验',$Condition_Name,$Condition_Value,0) == -1)
        {
            $Field_Name = ['实验名称','实验室地址','实验日期','实验课时','预约者'];
            $Field_Value = [$_POST['test_name'],$_POST['room_name'],$_POST['subscribe_date'],$_POST['subscribe_time'],$_POST['student_name']];
            if($DBManage->insert('已预约实验',$Field_Name,$Field_Value))
            {
                echo 'subscribe success';
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

    case 'DELETE_DEPLOYED':
        $i = 0;
        $Condition_Name = [];
        $Condition_Value = [];
        $Field_Name = ['名称','仪器个数'];
        $Result_1 = $DBManage->ItemsInTable($Field_Name,'实验仪器',$Condition_Name,$Condition_Value,0);

        $Condition_Name = ['实验名称','实验室地址'];
        $Condition_Value = [$_POST['test_name'],$_POST['room_name']];
        $Result = $DBManage->ItemsInTable($Field_Name,'下发实验',$Condition_Name,$Condition_Value,0);

        while($i < count($Result))
        {
            $Field_Name[$i] = $Result[$i][1] + $Result_1[$i][1];
            $Condition_Value[$i] = $Result_1[$i][0];
            $i++;
        }
        if($DBManage->update('实验仪器','仪器个数',$Field_Name,'名称',$Condition_Value))
        {
            $Condition_Value = [$_POST['test_name'],$_POST['room_name']];
            if($DBManage_1->Delete('下发实验',$Condition_Name,$Condition_Value))
            {
                $Field_Value = ['0'];
                $Condition_Value = [$_POST['room_name']];
                if($DBManage_1->update('实验室','使用中',$Field_Value,'实验室地址',$Condition_Value))
                {
                    $Field_Name = ['日期','内容'];
                    $Field_Value = [date("Y-m-d-H-i-s"),$_POST['teacher_name'].'老师取消了'.$_POST['test_name'].'实验,同学们请注意~~'];
                    if($DBManage_1->insert('通知通告',$Field_Name,$Field_Value))
                    {
                        echo 'delete success';
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
        break;

    case 'DELETE_SUBSCRIBED':
        $Condition_Name = ['实验名称','实验室地址','实验日期','实验课时'];
        $Condition_Value = [$_POST['test_name'],$_POST['room_name'],$_POST['subscribe_date'],$_POST['subscribe_time']];
        if($DBManage->Delete('已预约实验',$Condition_Name,$Condition_Value))
        {
            echo 'delete success';
        }
        else
        {
            echo '取消预约'.$Condition_Name[0].'失败';
        }
        break;

    case 'DELETE':
        $Condition_Name = [$_POST['Field_Name']];
        $Condition_Value = [$_POST['Field_Value']];
        if($DBManage->Delete($_POST['table_name'],$Condition_Name,$Condition_Value))
        {
            echo 'delete success';
        }
        else
        {
            echo '删除'.$_POST['table_name'].'失败';
        }
        break;

    case 'GET_TEST':
        $item = ['名称'];
        $Condition_Name = [];
        $Condition_Value = [];
        $Result = $DBManage->ItemsInTable($item,'实验',$Condition_Name,$Condition_Value,0);
        echo json_encode($Result);
        break;

    case 'GET_DEVICE':
        $item = ['名称','仪器个数'];
        $Condition_Name = [];
        $Condition_Value = [];
        $Result = $DBManage->ItemsInTable($item,'实验仪器',$Condition_Name,$Condition_Value,0);
        echo json_encode($Result);
        break;

    case 'GET_TESTROOM':
        $item = ['实验室地址'];
        $Condition_Name = ['使用中'];
        $Condition_Value = ['0'];
        $Result = $DBManage->ItemsInTable($item,'实验室',$Condition_Name,$Condition_Value,0);
        echo json_encode($Result);
        break;

    case 'DROP_TABLE':
        $DBManage->droptable($_POST['select_Name']);
        echo $DBManage->resultRecord;
        break;

    case 'GET_DEPLOYED':
        $item = ['实验名称','实验室地址','教师名称'];
        $Condition_Name = [];
        $Condition_Value = [];
        $Result = $DBManage->ItemsInTable($item,'下发实验',$Condition_Name,$Condition_Value,1);
        echo json_encode($Result);
        break;

    case 'GET_SUBSCRIBED':
        $item = ['实验名称','实验室地址','实验日期','实验课时'];
        $Condition_Name = ['预约者'];
        $Condition_Value = [$_POST['student_name']];
        $Result = $DBManage->ItemsInTable($item,'已预约实验',$Condition_Name,$Condition_Value,1);
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
            $Result[$i] = $DBManage->ItemsInTable($Field_List,$Table_List[$i],$Condition_Name,$Condition_Value,0);
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
        $Field_Value = ['实验名称','实验室地址','教师名称'];
        $Condition_Name = ['教师名称'];
        $Condition_Value = [$_POST['teacher_name']];
        $Result = $DBManage->ItemsInTable($Field_Value,'下发实验',$Condition_Name,$Condition_Value,1);
        echo json_encode($Result);
        break;

    case 'GET_ALLTABLE':
        $Result = $DBManage->selectAllTable();
        echo json_encode($Result);
        break;

    case 'LOGIN':
        session_start();
        if($_POST['name'] == ''||$_POST['password'] == ''||$_POST['captcha'] == '')
        {
            echo '请输入完整的登录信息!';
        }
        if($_POST['captcha'] != $_SESSION['phrase'])
        {
            echo '验证码错误！';
        }
        $Table_Name = $_POST['optradio'] ? '老师' : '学生';
        $item = ['姓名'];
        $Condition_Value = [$_POST['name']];
        if($DBManage->ItemsInTable($item,$Table_Name,$item,$Condition_Value,0) == 0)
        {
            echo '该用户不存在！';
        }
        else
        {
            $item = ['姓名','密码'];
            $Condition_Value = [$_POST['name'],$_POST['password']];
            if($DBManage->ItemsInTable($item,$Table_Name,$item,$Condition_Value,0) == 0)
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
        $Result = $DBManage->ItemsInTable($Field_Value,'通知通告',$Condition_Name,$Condition_Value,0);
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
    case 'GET_TECTITLE':
        $Field_Value = ['日期','名称'];
        $Condition_Name = [];
        $Condition_Value = [];
        $Result = $DBManage->ItemsInTable($Field_Value,$_POST['table_name'],$Condition_Name,$Condition_Value,0);
        echo json_encode($Result);
        break;

    case 'GET_TITLE':
        $Field_Value = ['名称'];
        $Condition_Name = [];
        $Condition_Value = [];
        $Result = $DBManage->ItemsInTable($Field_Value,$_POST['table_name'],$Condition_Name,$Condition_Value,0);
        echo json_encode($Result);
        break;

    case 'GET_CONTENT':
        $Field_Value = ['名称','类别','内容'];
        $Condition_Name = ['名称'];
        $Condition_Value = [$_POST['content_title']];
        $Result = $DBManage->ItemsInTable($Field_Value,$_POST['table_name'],$Condition_Name,$Condition_Value,0);
        echo json_encode($Result);
        break;

    case 'GET_DEVCONTENT':
        $Field_Value = ['名称','系别','内容'];
        $Condition_Name = ['名称'];
        $Condition_Value = [$_POST['content_title']];
        $Result = $DBManage->ItemsInTable($Field_Value,$_POST['table_name'],$Condition_Name,$Condition_Value,0);
        echo json_encode($Result);
        break;

    case 'GET_SUBSTUDENT':
        $Field_Value = ['预约者'];
        $Condition_Name = ['实验名称'];
        $Condition_Value = [$_POST['content_title']];
        $Result = $DBManage->ItemsInTable($Field_Value,$_POST['table_name'],$Condition_Name,$Condition_Value,1);
        echo json_encode($Result);
        break;

    case 'GET_TEACHERINFO':
        $item = ['姓名','教师信息'];
        $Condition_Name = [];
        $Condition_Value = [];
        $Result = $DBManage->ItemsInTable($item,'老师',$Condition_Name,$Condition_Value,0);
        echo json_encode($Result);
        break;

    case 'GET_ATTENTEST':
        $time =  get_currentClass();
        $item = ['实验名称','实验日期','实验课时','预约者'];
        $Condition_Name = ['实验日期','实验课时','预约者'];
        $Condition_Value = [date('Y-n-j'),$time,$_POST['student_name']];
        $Result = $DBManage->ItemsInTable($item,'已预约实验',$Condition_Name,$Condition_Value,0);
        if($Result == 1)
        {
            echo 'not now';
        }
        else
        {
            echo json_encode($Result);
        }
        break;

    case 'INSERT_ATTENDENCE':
        $Condition_Name = ['实验名称','实验日期','实验课时','预约者'];
        $Condition_Value = [$_POST['test_name'],$_POST['subscribe_date'],$_POST['subscribe_time'],$_POST['student_name']];
        if($DBManage->insert('考勤表',$Condition_Name,$Condition_Value))
        {
            echo 'insert success';
        }
        else
        {
            echo $DBManage->resultRecord;
        }
        break;
    default:
        break;
}
function get_currentClass()
{
    $current_time = date('H:i');
    if((strtotime($current_time) >= strtotime('08:00'))&&(strtotime($current_time) <= strtotime('9:35')))
    {
        return '上午一二节';
    }
    elseif ((strtotime($current_time) >= strtotime('9:50'))&&(strtotime($current_time) <= strtotime('11:25')))
    {
        return '上午三四节';
    }
    elseif ((strtotime($current_time) >= strtotime('11:30'))&&(strtotime($current_time) <= strtotime('12:15')))
    {
        return '上午第五节';
    }
    elseif ((strtotime($current_time) >= strtotime('13:30'))&&(strtotime($current_time) <= strtotime('15:05')))
    {
        return '下午一二节';
    }
    elseif ((strtotime($current_time) >= strtotime('15:20'))&&(strtotime($current_time) <= strtotime('16:55')))
    {
        return '下午三四节';
    }
    elseif ((strtotime($current_time) >= strtotime('18:30'))&&(strtotime($current_time) <= strtotime('20:05')))
    {
        return '晚上一二节';
    }
    elseif ((strtotime($current_time) >= strtotime('20:10'))&&(strtotime($current_time) <= strtotime('20:55')))
    {
        return '晚上三四节';
    }
    else
    {
        return -1;
    }
}