<?php
/**
 * 微信授权表
 */

namespace app\common\model;
class WeixinAuthor extends Common
{

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';
    const BINDTYPEAUTHOR = 1;//授权绑定
    const BINDTYPESELF = 2;//自助绑定

    /**
     * 保存授权信息
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
     * 查看是否能绑定
     * @param int $seller_id
     * @return string
     */
    public function checkBind($seller_id = 0, $authorType = 'b2c')
    {
        $author = $this->field('id')->where(['seller_id' => $seller_id, 'author_type' => $authorType])->find();
        return $author ? false : true;
    }

    /**
     * 获取author授权信息
     * @param int $seller_id
     * @param string $authorType
     * @param string $fields
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public function getAuthorInfo($seller_id = 0, $authorType = 'b2c',$fields='*')
    {
        $authorInfo = $this->where([
            'seller_id' => $seller_id,
            'author_type' => $authorType
        ])->field($fields)->find();

        if ($authorInfo && $authorInfo['bind_type'] == self::BINDTYPESELF) {
            $authorInfo['head_img'] = _sImage($authorInfo['head_img']);
        }
        return $authorInfo;
    }

    /**
     * 更新授权
     * @param $data
     * @return false|int
     */
    public function updateAccessToken($data)
    {
        $uData=[
            'authorizer_access_token'=>$data['authorizer_access_token'],
            'authorizer_refresh_token'=>$data['authorizer_refresh_token'],
            'expires_in'=>time()+$data['expires_in'],//默认为2小时
        ];
        return $this->where(['appid' => $data['AuthorizerAppid']])->save($uData);

    }

    /**
     * 取消授权时，授权过期时间改为0
     * @param $data
     * @return bool
     */
    public function unAuthorized($data)
    {
        $this->where(['appid' => $data['AuthorizerAppid']])->save(['expires_in' => 0]);
        return true;
    }

    /**
     * 根据appid获取授权信息
     * @param string $appid
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public function getAuthorInfoByAppId($appid='')
    {
        return $this->where(['appid'=>$appid])->find();
    }

}
