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

/* @TagManager/dashboard.twig */
class __TwigTemplate_7de1d3397f74b654007d93bd41da7f71 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "@TagManager/tagmanager.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 3
        $context["title"] = $this->env->getFilter('translate')->getCallable()("Dashboard_Dashboard");
        // line 1
        $this->parent = $this->loadTemplate("@TagManager/tagmanager.twig", "@TagManager/dashboard.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 5
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        yield "    <div
        vue-entry=\"TagManager.ContainerDashboard\"
        id-container=\"";
        // line 8
        yield \Piwik\piwik_escape_filter($this->env, json_encode(CoreExtension::getAttribute($this->env, $this->source, (isset($context["container"]) || array_key_exists("container", $context) ? $context["container"] : (function () { throw new RuntimeError('Variable "container" does not exist.', 8, $this->source); })()), "idcontainer", [], "any", false, false, false, 8)), "html", null, true);
        yield "\"
        dashboard-help-text=\"";
        // line 9
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["dashboardHelpText"]) || array_key_exists("dashboardHelpText", $context) ? $context["dashboardHelpText"] : (function () { throw new RuntimeError('Variable "dashboardHelpText" does not exist.', 9, $this->source); })())), "html", null, true);
        yield "\"
        tags-help-text=\"";
        // line 10
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["tagsHelpText"]) || array_key_exists("tagsHelpText", $context) ? $context["tagsHelpText"] : (function () { throw new RuntimeError('Variable "tagsHelpText" does not exist.', 10, $this->source); })())), "html", null, true);
        yield "\"
        triggers-help-text=\"";
        // line 11
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["triggersHelpText"]) || array_key_exists("triggersHelpText", $context) ? $context["triggersHelpText"] : (function () { throw new RuntimeError('Variable "triggersHelpText" does not exist.', 11, $this->source); })())), "html", null, true);
        yield "\"
        variables-help-text=\"";
        // line 12
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["variablesHelpText"]) || array_key_exists("variablesHelpText", $context) ? $context["variablesHelpText"] : (function () { throw new RuntimeError('Variable "variablesHelpText" does not exist.', 12, $this->source); })())), "html", null, true);
        yield "\"
        versions-help-text=\"";
        // line 13
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["versionsHelpText"]) || array_key_exists("versionsHelpText", $context) ? $context["versionsHelpText"] : (function () { throw new RuntimeError('Variable "versionsHelpText" does not exist.', 13, $this->source); })())), "html", null, true);
        yield "\">
    </div>

";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@TagManager/dashboard.twig";
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
        return array (  78 => 13,  74 => 12,  70 => 11,  66 => 10,  62 => 9,  58 => 8,  54 => 6,  50 => 5,  45 => 1,  43 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends '@TagManager/tagmanager.twig' %}

{% set title = 'Dashboard_Dashboard'|translate %}

{% block content %}
    <div
        vue-entry=\"TagManager.ContainerDashboard\"
        id-container=\"{{ container.idcontainer|json_encode }}\"
        dashboard-help-text=\"{{ dashboardHelpText|json_encode }}\"
        tags-help-text=\"{{ tagsHelpText|json_encode }}\"
        triggers-help-text=\"{{ triggersHelpText|json_encode }}\"
        variables-help-text=\"{{ variablesHelpText|json_encode }}\"
        versions-help-text=\"{{ versionsHelpText|json_encode }}\">
    </div>

{% endblock %}", "@TagManager/dashboard.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\TagManager\\templates\\dashboard.twig");
    }
}
