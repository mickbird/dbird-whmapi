<?php
declare(strict_types = 1);

namespace App\Components;

use App\Helpers\CsrfTokenHelper;
use Core\Component;
use Core\Controller;

/**
 * Class CsrfComponent provides CSRF based security
 * @package App\Components
 */
class CsrfComponent extends Component
{
    /*
     * FIELDS
     */
    private ?array $actions;
    private bool $shouldFilter;

    private CsrfTokenHelper $csrfHelper;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    /**
     * CsrfComponent constructor.
     * @param bool $shouldFilter True to enable for all by default. False to disable for all by default.
     */
    public function __construct(int $validityDuration, bool $shouldFilter = false)
    {
        $this->csrfHelper = new CsrfTokenHelper($validityDuration);
        $this->actions = null;
        $this->shouldFilter = $shouldFilter;
    }

    /*
     * PUBLIC METHODS
     */

    /**
     * Enable CSRF security for the provided actions only.
     * This is equivalent to disabling for all except these actions.
     * @param array|null $actions List of actions
     */
    public function enableFor(?array $actions) : void
    {
        $this->actions = $actions;
        $this->shouldFilter = true;
    }

    /**
     * Enable CSRF security for all actions.
     */
    public function enableByDefault() : void
    {
        $this->enableFor(null);
    }

    /**
     * Disable CSRF security for the provided actions only.
     * This is equivalent to enabling for all except these actions.
     * @param array|null $actions List of actions
     */
    public function disableFor(?array $actions) : void
    {
        $this->actions = $actions;
        $this->shouldFilter = false;
    }

    /**
     * Disable CSRF security for all actions.
     */
    public function disableByDefault() : void
    {
        $this->disableFor(null);
    }

    /**
     * Invoked before the controller call filter method.
     * @param Controller $controller The calling controller
     * @return bool True to continue processing. False to stop processing
     */
    public function beforeFilter(Controller $controller) : bool
    {
        if (!parent::beforeFilter($controller)) {
            return false;
        }

        $this->csrfHelper->clean();

        if ($this->shouldFilter($controller->getRequest()->getPrefix() . $controller->getRequest()->getAction()) === false) {
            return true;
        }

        $key = implode('_', [
            $controller->getRequest()->getController(),
            $controller->getRequest()->getPrefix() . $controller->getRequest()->getAction(),
            implode('', $controller->getRequest()->getParams('get'))
        ]);
        $formKey = '_csrf';

        switch ($controller->getRequest()->getMethod()) {
            case 'get':
                $controller->set($this->csrfHelper->getStoreKey(), ['key' => $formKey, 'value' => $this->csrfHelper->create($key)]);
                break;

            case 'post':
                $model = $controller->getRequest()->getParams('post');

                if (!$this->csrfHelper->verify($key, @$model[$formKey])) {
                    $controller->saveState($model);
                    $controller->flash()->warning(_('CSRF security'), _('The CSRF token is invalid'));
                    $controller->redirectSelf();
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Indicate if the component should apply.
     * @param string $action
     * @return bool true if the component should apply. Otherwise false
     */
    public function shouldFilter(string $action) : bool
    {
        if ($this->actions !== null) {
            $action = strtolower($action);
            $actions = array_map('strtolower', $this->actions);

            return in_array($action, $actions) === $this->shouldFilter;
        } else {
            return $this->shouldFilter;
        }
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
