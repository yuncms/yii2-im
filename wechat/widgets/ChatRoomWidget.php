<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\im\wechat\widgets;

use Yii;
use yii\base\Widget;
use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\InvalidConfigException;
use yuncms\im\wechat\assets\ImAsset;

/**
 * Class ChatRoomWidget
 * @package yuncms\im
 */
class ChatRoomWidget extends Widget
{
    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = [];

    public $appId;

    public $accountType;

    /**
     * @var string 用户标识
     */
    public $identifier;

    /**
     * @var string 用户昵称
     */
    public $identifierNick;

    /**
     * @var string 用户头像
     */
    public $headUrl;

    /**
     * @var string 房间号
     */
    public $avLiveRoomId;

    /**
     * @var string 用户签名
     */
    public $userSig;

    /**
     * Initializes the widget.
     */
    public function init()
    {
        parent::init();
        $this->initOptions();
        $this->registerAssets();
    }

    /**
     * Initializes the widget options
     */
    protected function initOptions()
    {
        $this->appId = Yii::$app->im->appId;
        $this->accountType = Yii::$app->im->accountType;
        if (empty ($this->identifier)) {
            throw new InvalidConfigException ('The "identifier" property must be set.');
        }
        if (empty ($this->avLiveRoomId)) {
            throw new InvalidConfigException ('The "avLiveRoomId" property must be set.');
        }
        if (empty($this->identifierNick)) {
            $this->identifierNick = $this->identifier;
        }
        $this->userSig = Yii::$app->im->genSig($this->identifier, 3600 * 24);
    }

    public function run()
    {

    }

    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
        $view = $this->getView();
        $asset = ImAsset::register($view);

        if (empty($this->headUrl)) {
            $this->headUrl = $asset->baseUrl . '/img/avatar.png';
        }
        $view->registerJs("
        var sdkAppID = {$this->appId};
        var accountType = {$this->accountType};
        var avChatRoomId = \"{$this->avLiveRoomId}\";
        var selType = webim.SESSION_TYPE.GROUP;
        var selToID = avChatRoomId;
        var selSess = null;
        var selSessHeadUrl = '{$this->headUrl}';
        var loginInfo = {
        'sdkAppID': sdkAppID,
        'appIDAt3rd': sdkAppID,
        'accountType': accountType,
        'identifier': '{$this->identifier}',
        'identifierNick': '{$this->identifierNick}',
        'userSig': '{$this->userSig}',
        'headurl': '{$this->headUrl}'
        };
        var onGroupSystemNotifys = {
            \"5\": onDestoryGroupNotify,
            \"11\": onRevokeGroupNotify,
            \"255\": onCustomGroupNotify
        };
        var onConnNotify = function (resp) {
            switch (resp.ErrorCode) {
                case webim.CONNECTION_STATUS.ON:
                    break;
                case webim.CONNECTION_STATUS.OFF:
                    webim.Log.warn('连接已断开，无法收到新消息，请检查下你的网络是否正常');
                    break;
                default:
                    webim.Log.error('未知连接状态,status=' + resp.ErrorCode);
                    break;
            }
        };
        var listeners = {
            \"onConnNotify\": onConnNotify,
            \"jsonpCallback\": function(rspData){
                webim.setJsonpLastRspData(rspData);
            },
            \"onBigGroupMsgNotify\": onBigGroupMsgNotify,
            \"onMsgNotify\": onMsgNotify,
            \"onGroupSystemNotifys\": onGroupSystemNotifys
        };
        var options = {
            'isAccessFormalEnv': true,
            'isLogOn': true
        };
        var curPlayAudio = null;
        var openEmotionFlag = false;", View::POS_END);
    }
}