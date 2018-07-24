<?php
/**
 * 小程序发布表
 */

namespace app\common\model;
class WeixinPublish extends Common
{

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';
    const AUDIT_STATUS_SUCCESS = 0;
    const AUDIT_STATUS_FAIL = 1;
    const AUDIT_STATUS_ING = 2;
    const AUDIT_STATUS_NO = -1;

    protected function tableWhere($post)
    {
        $where = [];
        if (isset($post['ctime']) && $post['ctime'] != "") {
            $date_array = explode('~', $post['ctime']);
            $sutime     = strtotime($date_array[0] . '00:00:00', time());
            $eutime     = strtotime($date_array[1] . '23:59:59', time());
            $where[]    = ['ctime', ['EGT', $sutime], ['ELT', $eutime], 'and'];
        }

        if (isset($post['audit_status']) && $post['audit_status'] !== "") {
            $where[] = ['audit_status', 'eq', $post['audit_status']];
        }

        if (isset($post['seller_id']) && $post['seller_id'] !== "") {
            $where[] = ['seller_id', 'eq', $post['seller_id']];
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = ['id' => 'desc'];
        return $result;
    }

    /**
     * 保存发布信息
     * @param array $data
     * @return int|string
     */
    public function doAdd($data = [])
    {
        $result = $this->save($data);
        if ($result) {
            return $this->getLastInsID();
        }
        return $result;
    }

    /**
     * 更新发布信息
     * @param array $data
     * @param array $filter
     * @return bool
     */
    public function updatePublish($data = [], $filter = [])
    {
        $result = $this->save($data, $filter);

        if ($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取商户小程序的最后一次的审核id
     * @param int $seller_id
     * @return bool|mixed
     */
    public function getAuditid($seller_id = 0)
    {
        if (!$seller_id) {
            return false;
        }
        $publish = $this->field('id,auditid')->where(['seller_id' => $seller_id])->order('ctime', 'desc')->find();
        return $publish ? $publish : false;
    }

    /**
     * 判断商户是否上传过代码，是否启用过模板
     * @param int $seller_id
     * @param string $appid
     * @param int $template_id
     * @return bool
     */
    public function searchPublish($seller_id = 0, $appid = '', $auditid = 0)
    {
        if (!$seller_id || !$appid) {
            return false;
        }
        $filter = [
            'seller_id' => $seller_id,
            'appid'     => $appid,
        ];
        if ($auditid !== false) {
            $filter['auditid'] = $auditid;
        }
        $publish = $this->field('id')->where($filter)->find();
        if ($publish != false) {
            return $publish['id'];
        }
        return false;
    }

    /**
     * 取出对应模板
     * @return \think\model\relation\HasOne
     */
    public function template()
    {
        return $this->hasOne('Template', 'id', 'template_id')->field('id,name')->bind('template');
    }

    public function tableFormat($list)
    {
        if (!$list->isEmpty()) {
            foreach ($list as $key => $val) {
                $list[$key]['ctime']         = getTime($val['ctime']);
                $list[$key]['template_name'] = $val->template['name'];
                switch ($val['audit_status']) {
                    case self::AUDIT_STATUS_SUCCESS:
                        $list[$key]['audit_status'] = "审核成功";
                        break;
                    case self::AUDIT_STATUS_FAIL:
                        $list[$key]['audit_status'] = "审核失败";

                        break;
                    case self::AUDIT_STATUS_ING:
                        $list[$key]['audit_status'] = "审核中";

                        break;
                    case self::AUDIT_STATUS_NO:
                        $list[$key]['audit_status'] = "已提交待审核";

                        break;
                    default :
                        $list[$key]['audit_status'] = "已提交待审核";
                        break;
                }
            }
        }
        return parent::tableFormat($list); // TODO: Change the autogenerated stub
    }

}
