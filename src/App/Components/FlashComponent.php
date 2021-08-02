<?php
declare(strict_types = 1);

namespace App\Components;

use App\Helpers\NotificationHelper;
use Core\Component;
use Core\Controller;

/**
 * Class FlashComponent provides flash notifications to the user
 * @package App\Components
 */
class FlashComponent extends Component
{
    /*
     * FIELDS
     */

    protected NotificationHelper $notificationHelper;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * FlashComponent constructor.
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->notificationHelper = new NotificationHelper($key);
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Add a info notification
     * @param string $title
     * @param string $content
     * @param array|null $additionalContents
     */
    public function info(string $title, string $content, ?array $additionalContents = null) : void
    {
        $this->notificationHelper->push('info', $title, $content, $additionalContents);
    }

    /**
     * Add a success notification
     * @param string $title
     * @param string $content
     * @param array|null $additionalContents
     */
    public function success(string $title, string $content, ?array $additionalContents = null) : void
    {
        $this->notificationHelper->push('success', $title, $content, $additionalContents);
    }

    /**
     * Add a warning notification
     * @param string $title
     * @param string $content
     * @param array|null $additionalContents
     */
    public function warning(string $title, string $content, ?array $additionalContents = null) : void
    {
        $this->notificationHelper->push('warning', $title, $content, $additionalContents);
    }

    /**
     * Add a danger notification
     * @param string $title
     * @param string $content
     * @param array|null $additionalContents
     */
    public function danger(string $title, string $content, ?array $additionalContents = null) : void
    {
        $this->notificationHelper->push('danger', $title, $content, $additionalContents);
    }

    /**
     * Invoked before the controller call filter method.
     * @param Controller $controller The calling controller
     */
    public function beforeRender(Controller $controller) : void
    {
        $controller->set($this->notificationHelper->getStoreKey(), $this->notificationHelper->getAll());
        $this->notificationHelper->clear();
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
