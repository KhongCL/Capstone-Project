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

/* dashboard.twig */
class __TwigTemplate_10ca3705d5950b59539579e78ac62b2a extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'head' => [$this, 'block_head'],
            'pageDescription' => [$this, 'block_pageDescription'],
            'body' => [$this, 'block_body'],
            'root' => [$this, 'block_root'],
            'topcontrols' => [$this, 'block_topcontrols'],
            'notification' => [$this, 'block_notification'],
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "layout.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 7
        $context["title"] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
            yield (isset($context["siteName"]) || array_key_exists("siteName", $context) ? $context["siteName"] : (function () { throw new RuntimeError('Variable "siteName" does not exist.', 7, $this->source); })());
            yield " - ";
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreHome_WebAnalyticsReports"), "html", null, true);
            return; yield '';
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 11
        $context["bodyClass"] = $this->env->getFunction('postEvent')->getCallable()("Template.bodyClass", "dashboard");
        // line 1
        $this->parent = $this->loadTemplate("layout.twig", "dashboard.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_head($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        yield "    ";
        yield from $this->yieldParentBlock("head", $context, $blocks);
        yield "
";
        return; yield '';
    }

    // line 9
    public function block_pageDescription($context, array $blocks = [])
    {
        $macros = $this->macros;
        yield "Web Analytics report for ";
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["siteName"]) || array_key_exists("siteName", $context) ? $context["siteName"] : (function () { throw new RuntimeError('Variable "siteName" does not exist.', 9, $this->source); })()), "html_attr");
        yield " - Matomo";
        return; yield '';
    }

    // line 13
    public function block_body($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 14
        yield "    ";
        yield $this->env->getFunction('postEvent')->getCallable()("Template.header", "dashboard");
        yield "
    ";
        // line 15
        yield from $this->yieldParentBlock("body", $context, $blocks);
        yield "
    ";
        // line 16
        yield $this->env->getFunction('postEvent')->getCallable()("Template.footer", "dashboard");
        yield "
";
        return; yield '';
    }

    // line 19
    public function block_root($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 20
        yield "    ";
        yield from         $this->loadTemplate("@CoreHome/_warningInvalidHost.twig", "dashboard.twig", 20)->unwrap()->yield($context);
        // line 21
        yield "    ";
        yield from         $this->loadTemplate("@CoreHome/_topScreen.twig", "dashboard.twig", 21)->unwrap()->yield($context);
        // line 22
        yield "
    <div class=\"top_controls\">
        ";
        // line 24
        if (( !array_key_exists("hideQuickAccess", $context) ||  !(isset($context["hideQuickAccess"]) || array_key_exists("hideQuickAccess", $context) ? $context["hideQuickAccess"] : (function () { throw new RuntimeError('Variable "hideQuickAccess" does not exist.', 24, $this->source); })()))) {
            // line 25
            yield "            <div vue-entry=\"CoreHome.QuickAccess\" class=\"piwikTopControl borderedControl\"></div>
        ";
        }
        // line 27
        yield "
        ";
        // line 28
        yield from $this->unwrap()->yieldBlock('topcontrols', $context, $blocks);
        // line 30
        yield "    </div>

    <div class=\"ui-confirm\" id=\"alert\">
        <h2></h2>
        <input role=\"yes\" type=\"button\" value=\"";
        // line 34
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("General_Ok"), "html", null, true);
        yield "\"/>
    </div>

    ";
        // line 37
        yield $this->env->getFunction('postEvent')->getCallable()("Template.beforeContent", "dashboard", (isset($context["currentModule"]) || array_key_exists("currentModule", $context) ? $context["currentModule"] : (function () { throw new RuntimeError('Variable "currentModule" does not exist.', 37, $this->source); })()), (isset($context["currentAction"]) || array_key_exists("currentAction", $context) ? $context["currentAction"] : (function () { throw new RuntimeError('Variable "currentAction" does not exist.', 37, $this->source); })()));
        yield "

    <div class=\"page\">

        ";
        // line 41
        if ((array_key_exists("showMenu", $context) && (isset($context["showMenu"]) || array_key_exists("showMenu", $context) ? $context["showMenu"] : (function () { throw new RuntimeError('Variable "showMenu" does not exist.', 41, $this->source); })()))) {
            // line 42
            yield "            <div id=\"secondNavBar\" class=\"Menu--dashboard z-depth-1\">
                <div vue-entry=\"CoreHome.ReportingMenu\"></div>
            </div>
        ";
        }
        // line 46
        yield "
        <div class=\"pageWrap\">

            <a name=\"main\"></a>
            ";
        // line 50
        yield from $this->unwrap()->yieldBlock('notification', $context, $blocks);
        // line 53
        yield "
            <div vue-entry=\"CoreHome.Comparisons\"></div>

            ";
        // line 56
        yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
        // line 58
        yield "
            <div class=\"clear\"></div>
        </div>

    </div>

";
        // line 64
        if ((array_key_exists("whatisnewShow", $context) && (isset($context["whatisnewShow"]) || array_key_exists("whatisnewShow", $context) ? $context["whatisnewShow"] : (function () { throw new RuntimeError('Variable "whatisnewShow" does not exist.', 64, $this->source); })()))) {
            // line 65
            yield "    <script>
        document.addEventListener(\"DOMContentLoaded\", function(event) {
            const tooltip = '";
            // line 67
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_WhatIsNewTooltip"), "html", null, true);
            yield "';
            window.Piwik_Popover.createPopupAndLoadUrl('module=CoreAdminHome&action=whatIsNew', tooltip.replace(/&#039;/g,\"'\"), 'what-is-new-popup');
        });
    </script>
";
        }
        // line 72
        yield "
";
        return; yield '';
    }

    // line 28
    public function block_topcontrols($context, array $blocks = [])
    {
        $macros = $this->macros;
        yield "        ";
        return; yield '';
    }

    // line 50
    public function block_notification($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 51
        yield "                ";
        yield from         $this->loadTemplate("@CoreHome/_notifications.twig", "dashboard.twig", 51)->unwrap()->yield($context);
        // line 52
        yield "            ";
        return; yield '';
    }

    // line 56
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        yield "            ";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "dashboard.twig";
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
        return array (  220 => 56,  215 => 52,  212 => 51,  208 => 50,  200 => 28,  194 => 72,  186 => 67,  182 => 65,  180 => 64,  172 => 58,  170 => 56,  165 => 53,  163 => 50,  157 => 46,  151 => 42,  149 => 41,  142 => 37,  136 => 34,  130 => 30,  128 => 28,  125 => 27,  121 => 25,  119 => 24,  115 => 22,  112 => 21,  109 => 20,  105 => 19,  98 => 16,  94 => 15,  89 => 14,  85 => 13,  75 => 9,  67 => 4,  63 => 3,  58 => 1,  56 => 11,  49 => 7,  42 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends 'layout.twig' %}

{% block head %}
    {{ parent() }}
{% endblock %}

{% set title %}{{ siteName|raw }} - {{ 'CoreHome_WebAnalyticsReports'|translate }}{% endset %}

{% block pageDescription %}Web Analytics report for {{ siteName|escape(\"html_attr\") }} - Matomo{% endblock %}

{% set bodyClass = postEvent('Template.bodyClass', 'dashboard') %}

{% block body %}
    {{ postEvent(\"Template.header\", \"dashboard\") }}
    {{ parent() }}
    {{ postEvent(\"Template.footer\", \"dashboard\") }}
{% endblock %}

{% block root %}
    {% include \"@CoreHome/_warningInvalidHost.twig\" %}
    {% include \"@CoreHome/_topScreen.twig\" %}

    <div class=\"top_controls\">
        {% if hideQuickAccess is not defined or not hideQuickAccess %}
            <div vue-entry=\"CoreHome.QuickAccess\" class=\"piwikTopControl borderedControl\"></div>
        {% endif %}

        {% block topcontrols %}
        {% endblock %}
    </div>

    <div class=\"ui-confirm\" id=\"alert\">
        <h2></h2>
        <input role=\"yes\" type=\"button\" value=\"{{ 'General_Ok'|translate }}\"/>
    </div>

    {{ postEvent(\"Template.beforeContent\", \"dashboard\", currentModule, currentAction) }}

    <div class=\"page\">

        {% if showMenu is defined and showMenu %}
            <div id=\"secondNavBar\" class=\"Menu--dashboard z-depth-1\">
                <div vue-entry=\"CoreHome.ReportingMenu\"></div>
            </div>
        {% endif %}

        <div class=\"pageWrap\">

            <a name=\"main\"></a>
            {% block notification %}
                {% include \"@CoreHome/_notifications.twig\" %}
            {% endblock %}

            <div vue-entry=\"CoreHome.Comparisons\"></div>

            {% block content %}
            {% endblock %}

            <div class=\"clear\"></div>
        </div>

    </div>

{% if whatisnewShow is defined and whatisnewShow %}
    <script>
        document.addEventListener(\"DOMContentLoaded\", function(event) {
            const tooltip = '{{ 'CoreAdminHome_WhatIsNewTooltip'|translate }}';
            window.Piwik_Popover.createPopupAndLoadUrl('module=CoreAdminHome&action=whatIsNew', tooltip.replace(/&#039;/g,\"'\"), 'what-is-new-popup');
        });
    </script>
{% endif %}

{% endblock %}
", "dashboard.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\Morpheus\\templates\\dashboard.twig");
    }
}
