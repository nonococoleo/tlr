<?php

namespace app\tlr\controller;

use app\tlr\model\LogModel;
use app\tlr\model\PayModel;
use app\tlr\model\RefundModel;
use app\tlr\model\SemesterModel;
use gmars\rbac\Rbac;
use think\Controller;
use think\Request;


class Refund extends Controller
{
    //首页显示所有退费情况
    public function index(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "index/index", null, 3);
            exit();
        }
        $Refund = new RefundModel();
        $state = $request->param('state');
        $seme = $request->param('seme');
        $Semester = new SemesterModel();
        $semester = $Semester->where("id", ">", 0)->where("current", ">=", 0)->select();
        $refund = $Refund->alias("r")->where("r.delflag", "=", 0)->join("semester s", "s.id=r.semester")->join("pay p", "p.rid=r.rid");
        if (!$state)
            $state = 0;
        else
            $refund = $refund->where("state", "=", "$state");
        if (!$seme)
            $seme = 0;
        else
            $refund = $refund->where("semester", "=", $seme);
        $query = ["seme" => $seme, "state" => $state];
        $refund = $refund->field("r.rid,r.semester,r.fee,r.method,r.card,r.bank,r.person,r.date,r.state,r.uid,r.detail,p.pid,name")->paginate(10, false, ['type' => 'bootstrap', 'query' => $query]);

        if ($refund->count() > 0) {
            $page = $refund->render();

            $data = ["refund" => $refund, "page" => $page, "semester" => $semester, "state" => $state, "seme" => $seme];
            $this->assign($data);

            $htmls = $this->fetch("index");
            return $htmls;
        } else {
            $this->error("查无记录", null, null, 1);
            return null;
        }
    }

    //根据ID获取退费
    public function refById(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "Refund/index", null, 1);
            exit();
        }
        $Refund = new RefundModel();
        echo json_encode(array('ref'=>$Refund->where('rid', $_POST['rid'])->find(),'success'=>true));
    }

    //添加退费信息接口
    public function add(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "Refund/index", null, 1);
            exit();
        }
        $Refund = new RefundModel();
        $Log = new LogModel();
        $Pay = new PayModel();
        $pid = $request->param("pid");
        unset($_POST["pid"]);
        foreach ($_POST as $key => $value)
            if ($value == "")
                $_POST[$key] = null;
        $Refund->save($_POST);
        $Pay->save(["rid" => $Refund->getLastInsID()], ['pid' => $pid]);
        $Log->save(["uid" => session('uid'), "action" => $Refund->getlastsql(), "time" => date("Y-m-d H:i:s")]);
//        $Enroll = new EnrollModel();
//        $Enroll->save(["delflag" => 1], ['pid' => $pid]);
        $this->success("添加成功", null, null, 1);
        return null;
    }

    //删除退费信息接口
    public function del(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "Refund/index", null, 1);
            exit();
        }
        $Refund = new RefundModel();
        $Log = new LogModel();
        $Refund->save(["delflag" => 1], ['rid' => $request->param("rid")]);
        $Log->save(["uid" => session('uid'), "action" => $Refund->getlastsql(), "time" => date("Y-m-d H:i:s")]);
        $this->success("删除成功", null, null, 1);
        return null;
    }

    //修改退费信息接口
    public function mod(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "Refund/index", null, 1);
            exit();
        }
        $Refund = new RefundModel();
        $Log = new LogModel();
        foreach ($_POST as $key => $value)
            if ($value == "")
                $_POST[$key] = null;
        $Refund->allowField(['fee', 'method', 'card', 'bank', 'person', 'state', 'memo', 'uid'])->save($_POST, ['rid' => $request->param("rid")]);
        $Log->save(["uid" => session('uid'), "action" => $Refund->getlastsql(), "time" => date("Y-m-d H:i:s")]);
        $this->success("修改成功", null, null, 1);
        return null;
    }
}
