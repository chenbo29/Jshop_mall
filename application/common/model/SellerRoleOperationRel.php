<?php
/**
 * 商户角色和权限关联关系表
 */

namespace app\common\model;

class SellerRoleOperationRel extends Common
{




    public function savePerm($role_id,$operations)
    {
        //先删除此角色的所有权限
        $this->where(['seller_role_id' => $role_id])->delete();

        $row['seller_role_id'] = $role_id;
        foreach($operations as $k => $v){
            $row['operation_id'] = $v['id'];
            $data[] = $row;
        }
        $this->saveAll($data);
        return true;

    }

    /**
     * 取得角色的权限列表，如果role_id == 0 那么就是超级管理员，取所有的
     * @param int $role_id
     */
    public function getTree($role_id = 0)
    {
        $operationModel = new Operation();
        if($role_id == 0){
            $tree = $operationModel->menuTree($operationModel::MENU_SELLER);
        }else{
            $tree = $this->permTree($role_id,$operationModel::MENU_SELLER);
        }
        return $tree;
    }

    /**
     * 结构和operation里的此方法差不多，但是这里的数据是当前模型和操作表关联查出来的，是角色的仅有的操作递归查询出来
     * @param $pid
     * @param $defaultNode
     * @return array|\PDOStatement|string|\think\Collection
     */
    private function permTree($role_id,$pid,$defaultNode=""){
        $where['o.parent_menu_id'] = $pid;
        $where['o.perm_type'] = 1;              //主体权限
        $where['sror.seller_role_id'] = $role_id;

        $list = $this
            ->field('o.*')
            ->alias('sror')
            ->join('operation o', 'o.id = sror.operation_id')
            ->where($where)
            ->select();
        foreach($list as $k => $v){
            $list[$k]['checkboxValue'] = $v['id'];
            //$list[$k]['checked'] = true;
            $list[$k]['children'] = $this->permTree($role_id,$v['id'],$defaultNode);
        }
        return $list;
    }

    /**
     * 判断用户是否有当前店铺和当前的操作有权限
     * @param $seller_id
     * @param $user_id
     * @param $p_id     在operation表中的模块的id
     * @param $cont_name
     * @param $act_name
     */
    public function checkPerm($seller_id,$user_id,$p_id,$cont_name,$act_name)
    {
        $result = [
            'status'=> false,
            'data' => '',
            'msg' => ''
        ];

        //如果判断用户是否是此店铺的管理员或者超级管理员
        $sellerManageModel = new SellerManage();
        $sellerList = $sellerManageModel->getSellerManage($user_id,$seller_id);

        if(!isset($sellerList[$seller_id])){
            return error_code(11086);
        }

        $operationModel = new Operation();

        //如果是指定的模块，就直接返回，这些模块不做权限操作
        if($operationModel->checkNeedPerm($p_id,$cont_name,$act_name)){
            $result['status'] = true;
            return $result;
        }

        //如果是超级管理员，直接返回
        if($sellerList[$seller_id] == $sellerManageModel::TYPE_SUPER){
            //判断当前控制器和方法是否在operation总保存，养成一个好习惯
//            $reinfo = $operationModel->getOperationInfo($cont_name,$act_name,$p_id);
//            if(!$reinfo['status']){
//                return $reinfo;
//            }


            $result['status'] = true;
            return $result;
        }

        //到这里就说明用户是店铺的普通管理员，那么就取所有的角色所对应的权限
        $list = $this
            ->distinct(true)
            ->field('o.*')
            ->alias('sror')
            ->join('operation o', 'o.id = sror.operation_id')
            ->where('sror.seller_role_id','IN',$sellerList[$seller_id])
            ->select();
        if($list->isEmpty()){
            return error_code(10010);       //可怜的，一个权限都没有
        }
        $newList = array_column($list->toArray(),'name','id');

        //查控制器所对应的操作记录
        $contWhere['type'] = 'c';
        $contWhere['parent_id'] = $p_id;
        $contWhere['code'] = $cont_name;
        $contOperation = $operationModel->where($contWhere)->find();
        if(!$contOperation){
            return error_code(11088);
        }
        //查询方法
        $actWhere['type'] = 'a';
        $actWhere['parent_id'] = $contOperation['id'];
        $actWhere['code'] = $act_name;
        $actOperation = $operationModel->where($actWhere)->find();
        if(!$actOperation){
            return error_code(11089);
        }
        //查看是否是是关联权限，如果是关联权限去查找对应的关联操作的权限
        if($actOperation['perm_type'] == $operationModel::PERM_TYPE_REL){
            $actOperation = $operationModel->where(['id'=>$actOperation['parent_menu_id']])->find();
            if(!$actOperation){
                return error_code(11090);
            }
        }
        //万事具备，只欠东风了
        if(!isset($newList[$actOperation['id']])){
            return error_code(10010);
        }
        $result['status'] = true;
        return $result;
    }



}