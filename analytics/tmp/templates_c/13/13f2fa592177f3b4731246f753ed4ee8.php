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

/* @MultiSites/allWebsitesDashboard.twig */
class __TwigTemplate_c31cfe2c60e47375a3e1f99d8cde6554 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'root' => [$this, 'block_root'],
            'topcontrols' => [$this, 'block_topcontrols'],
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return $this->loadTemplate((((isset($context["isWidgetized"]) || array_key_exists("isWidgetized", $context) ? $context["isWidgetized"] : (function () { throw new RuntimeError('Variable "isWidgetized" does not exist.', 1, $this->source); })())) ? ("empty.twig") : ("dashboard.twig")), "@MultiSites/allWebsitesDashboard.twig", 1);
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 3
        $context["bodyId"] = "awd";
        // line 1
        yield from $this->getParent($context)->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 5
    public function block_root($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        yield "    ";
        $context["hideQuickAccess"] = true;
        // line 7
        yield "    ";
        yield from $this->yieldParentBlock("root", $context, $blocks);
        yield "
";
        return; yield '';
    }

    // line 10
    public function block_topcontrols($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 11
        yield "    <div class=\"row\">
        <div class=\"col s12 m12 l10 offset-l1\">
            ";
        // line 13
        yield from         $this->loadTemplate("@CoreHome/_periodSelect.twig", "@MultiSites/allWebsitesDashboard.twig", 13)->unwrap()->yield($context);
        // line 14
        yield "        </div>
    </div>
";
        return; yield '';
    }

    // line 18
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 19
        yield "    <div class=\"reporting-page row\" id=\"multisitesAllWebsitesDashboard\">
        <div class=\"col s12 m12 l10 offset-l1\">
            ";
        // line 21
        if ((isset($context["isWidgetized"]) || array_key_exists("isWidgetized", $context) ? $context["isWidgetized"] : (function () { throw new RuntimeError('Variable "isWidgetized" does not exist.', 21, $this->source); })())) {
            // line 22
            yield "                <div id=\"main\">
            ";
        } else {
            // line 24
            yield "                <div id=\"main\" class=\"card\">
                    <div class=\"card-content\">
            ";
        }
        // line 27
        yield "
            <div vue-entry=\"MultiSites.AllWebsitesDashboard\"
                 auto-refresh-interval=\"";
        // line 29
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["autoRefreshTodayReport"]) || array_key_exists("autoRefreshTodayReport", $context) ? $context["autoRefreshTodayReport"] : (function () { throw new RuntimeError('Variable "autoRefreshTodayReport" does not exist.', 29, $this->source); })())), "html", null, true);
        yield "\"
                 display-revenue=\"";
        // line 30
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["displayRevenueColumn"]) || array_key_exists("displayRevenueColumn", $context) ? $context["displayRevenueColumn"] : (function () { throw new RuntimeError('Variable "displayRevenueColumn" does not exist.', 30, $this->source); })())), "html", null, true);
        yield "\"
                 display-sparklines=\"";
        // line 31
        if ((isset($context["show_sparklines"]) || array_key_exists("show_sparklines", $context) ? $context["show_sparklines"] : (function () { throw new RuntimeError('Variable "show_sparklines" does not exist.', 31, $this->source); })())) {
            yield "true";
        } else {
            yield "false";
        }
        yield "\"
                 is-widgetized=\"";
        // line 32
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["isWidgetized"]) || array_key_exists("isWidgetized", $context) ? $context["isWidgetized"] : (function () { throw new RuntimeError('Variable "isWidgetized" does not exist.', 32, $this->source); })())), "html", null, true);
        yield "\"
                 page-size=\"";
        // line 33
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("limit", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["limit"]) || array_key_exists("limit", $context) ? $context["limit"] : (function () { throw new RuntimeError('Variable "limit" does not exist.', 33, $this->source); })()), 25)) : (25))), "html", null, true);
        yield "\"
            ></div>

            ";
        // line 36
        if ((isset($context["isWidgetized"]) || array_key_exists("isWidgetized", $context) ? $context["isWidgetized"] : (function () { throw new RuntimeError('Variable "isWidgetized" does not exist.', 36, $this->source); })())) {
            // line 37
            yield "                </div>
            ";
        } else {
            // line 39
            yield "                    </div>
                </div>
            ";
        }
        // line 42
        yield "        </div>
    </div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@MultiSites/allWebsitesDashboard.twig";
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
        return array (  143 => 42,  138 => 39,  134 => 37,  132 => 36,  126 => 33,  122 => 32,  114 => 31,  110 => 30,  106 => 29,  102 => 27,  97 => 24,  93 => 22,  91 => 21,  87 => 19,  83 => 18,  76 => 14,  74 => 13,  70 => 11,  66 => 10,  58 => 7,  55 => 6,  51 => 5,  47 => 1,  45 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends isWidgetized ? 'empty.twig' : 'dashboard.twig' %}

{% set bodyId = 'awd' %}

{% block root %}
    {% set hideQuickAccess = true %}
    {{ parent() }}
{% endblock %}

{% block topcontrols %}
    <div class=\"row\">
        <div class=\"col s12 m12 l10 offset-l1\">
            {% include \"@CoreHome/_periodSelect.twig\" %}
        </div>
    </div>
{% endblock %}

{% block content %}
    <div class=\"reporting-page row\" id=\"multisitesAllWebsitesDashboard\">
        <div class=\"col s12 m12 l10 offset-l1\">
            {% if isWidgetized %}
                <div id=\"main\">
            {% else %}
                <div id=\"main\" class=\"card\">
                    <div class=\"card-content\">
            {% endif %}

            <div vue-entry=\"MultiSites.AllWebsitesDashboard\"
                 auto-refresh-interval=\"{{ autoRefreshTodayReport|json_encode }}\"
                 display-revenue=\"{{ displayRevenueColumn|json_encode }}\"
                 display-sparklines=\"{% if show_sparklines %}true{% else %}false{%endif%}\"
                 is-widgetized=\"{{ isWidgetized|json_encode }}\"
                 page-size=\"{{ limit|default(25)|json_encode }}\"
            ></div>

            {% if isWidgetized %}
                </div>
            {% else %}
                    </div>
                </div>
            {% endif %}
        </div>
    </div>
{% endblock %}
", "@MultiSites/allWebsitesDashboard.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\MultiSites\\templates\\allWebsitesDashboard.twig");
    }
}
