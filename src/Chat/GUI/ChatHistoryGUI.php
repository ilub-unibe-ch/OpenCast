<?php

namespace srag\Plugins\Opencast\Chat\GUI;

use arException;
use ilObjUser;
use ilOpenCastPlugin;
use ilTemplate;
use ilTemplateException;
use srag\Plugins\Opencast\Chat\Model\MessageAR;

/**
 * Class ChatHistoryGUI
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ChatHistoryGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    /**
     * @var integer
     */
    private $chat_room_id;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;

    /**
     * ChatHistoryGUI constructor.
     *
     * @param $chat_room_id
     */
    public function __construct($chat_room_id)
    {
        global $DIC;
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->chat_room_id = $chat_room_id;
    }

    /**
     * @param bool $async
     *
     * @return string
     * @throws DICException
     * @throws arException
     * @throws ilTemplateException
     */
    public function render($async = false)
    {
        // TODO: get rid of self::plugin() to be independent
        $template = new ilTemplate(self::plugin()->directory() . '/src/Chat/GUI/templates/history.html', true, true);
        $users = [];
        foreach (
            MessageAR::where(['chat_room_id' => $this->chat_room_id])->orderBy('sent_at', 'ASC')->get() as $message
        ) {
            $template->setCurrentBlock('message');
            /** @var $message MessageAR */
            $template->setVariable('USER_ID', $message->getUsrId());
            $template->setVariable('MESSAGE', $message->getMessage());
            $user = $users[$message->getUsrId()] ?: ($users[$message->getUsrId()] = new ilObjUser(
                $message->getUsrId()
            ));
            $template->setVariable('PUBLIC_NAME', $user->hasPublicProfile() ? $user->getFullname() : $user->getLogin());
            $template->setVariable('SENT_AT', date('H:i', strtotime($message->getSentAt())));
            $profile_picture_path = './data/' . CLIENT_ID . '/usr_images/usr_' . $message->getUsrId() . '_xsmall.jpg';
            $picture_path = is_file(
                $profile_picture_path
            ) ? $profile_picture_path : './templates/default/images/no_photo_xsmall.jpg';
            $template->setVariable('PROFILE_PICTURE_PATH', $picture_path);
            $template->parseCurrentBlock();
        }

        $chat_css_path = self::plugin()->directory() . '/src/Chat/node/public/css/chat.css';
        if (!$async) {
            $this->main_tpl->addCss($chat_css_path);
        } else {
            $template->setCurrentBlock('css');
            $template->setVariable('CSS_PATH', $chat_css_path);
            $template->parseCurrentBlock();
        }

        return $template->get();
    }
}
