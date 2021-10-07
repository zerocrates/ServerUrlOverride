<?php
namespace ServerUrlOverride;

use Omeka\Module\AbstractModule;
use Laminas\Form\Form;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $overrideServerUrl = $settings->get('override_server_url', '');

        if (!(is_string($overrideServerUrl) && $overrideServerUrl)) {
            return;
        }

        $parts = parse_url($overrideServerUrl);
        if (!(isset($parts['scheme']) && isset($parts['host']))) {
            return;
        }

        $scheme = $parts['scheme'];
        if (!($scheme === 'http' || $scheme === 'https')) {
            return;
        }

        $host = $parts['host'];

        if (isset($parts['port'])) {
            $port = $parts['port'];
        } elseif ($parts['scheme'] === 'http') {
            $port = 80;
        } else {
            $port = 443;
        }

        $viewHelperManager = $services->get('ViewHelperManager');
        $serverUrlHelper = $viewHelperManager->get('ServerUrl');
        $serverUrlHelper->setPort($port);
        $serverUrlHelper->setScheme($scheme);
        $serverUrlHelper->setHost($host);
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        $settings->delete('override_server_url');
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(Form::class);
        $form->add([
            'type' => 'Url',
            'name' => 'override_server_url',
            'options' => [
                'label' => 'Server URL', // @translate
                'info' => 'Include http:// or https://. Only host, port, and scheme are respected.', // @translate
            ],
            'attributes' => [
                'id' => 'override-server-url-input',
                'value' => $settings->get('override_server_url'),
            ],
        ]);
        return $renderer->formCollection($form, false);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $overrideServerUrl = $controller->params()->fromPost('override_server_url');
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->set('override_server_url', $overrideServerUrl);
    }
}
