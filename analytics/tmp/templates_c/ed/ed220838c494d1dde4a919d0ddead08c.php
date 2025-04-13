<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* @TagManager/trackingCode.twig */
class __TwigTemplate_1ed88d2445d7eea38d63d54cdfd61252 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        yield "<div
        vue-entry=\"TagManager.TrackingCodePage\"
        current-action=\"";
        // line 3
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["action"]) || array_key_exists("action", $context) ? $context["action"] : (function () { throw new RuntimeError('Variable "action" does not exist.', 3, $this->source); })())), "html", null, true);
        yield "\"
        show-container-row=\"";
        // line 4
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["showContainerRow"]) || array_key_exists("showContainerRow", $context) ? $context["showContainerRow"] : (function () { throw new RuntimeError('Variable "showContainerRow" does not exist.', 4, $this->source); })())), "html", null, true);
        yield "\"
        is-js-tracker-install-check-available=\"";
        // line 5
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["isJsTrackerInstallCheckAvailable"]) || array_key_exists("isJsTrackerInstallCheckAvailable", $context) ? $context["isJsTrackerInstallCheckAvailable"] : (function () { throw new RuntimeError('Variable "isJsTrackerInstallCheckAvailable" does not exist.', 5, $this->source); })())), "html", null, true);
        yield "\"
>
</div>";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@TagManager/trackingCode.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  50 => 5,  46 => 4,  42 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<div
        vue-entry=\"TagManager.TrackingCodePage\"
        current-action=\"{{ action|json_encode }}\"
        show-container-row=\"{{ showContainerRow|json_encode }}\"
        is-js-tracker-install-check-available=\"{{ isJsTrackerInstallCheckAvailable|json_encode }}\"
>
</div>", "@TagManager/trackingCode.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\TagManager\\templates\\trackingCode.twig");
    }
}
