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

/* @Installation/trackingCode.twig */
class __TwigTemplate_6680588790d53cd75b041bad9b16fad6 extends Template
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
        return "@Installation/layout.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("@Installation/layout.twig", "@Installation/trackingCode.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        yield "
    ";
        // line 5
        if (array_key_exists("displayfirstWebsiteSetupSuccess", $context)) {
            // line 6
            yield "        <div id=\"feedback\" class=\"alert alert-success\">
            ";
            // line 7
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_SetupWebsiteSetupSuccess", (isset($context["displaySiteName"]) || array_key_exists("displaySiteName", $context) ? $context["displaySiteName"] : (function () { throw new RuntimeError('Variable "displaySiteName" does not exist.', 7, $this->source); })())), "html", null, true);
            yield "
        </div>
    ";
        }
        // line 10
        yield "
    ";
        // line 11
        yield (isset($context["trackingHelp"]) || array_key_exists("trackingHelp", $context) ? $context["trackingHelp"] : (function () { throw new RuntimeError('Variable "trackingHelp" does not exist.', 11, $this->source); })());
        yield "

    <h3>";
        // line 13
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_LargePiwikInstances"), "html", null, true);
        yield "</h3>
    <p>
        ";
        // line 15
        yield $this->env->getFilter('translate')->getCallable()("Installation_JsTagArchivingHelp1", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/docs/setup-auto-archiving/"), "</a>");
        yield "
    </p>
    <p>
        ";
        // line 18
        yield $this->env->getFilter('translate')->getCallable()("General_ReadThisToLearnMore", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/docs/optimize/"), "</a>");
        yield "
    </p>

";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@Installation/trackingCode.twig";
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
        return array (  84 => 18,  78 => 15,  73 => 13,  68 => 11,  65 => 10,  59 => 7,  56 => 6,  54 => 5,  51 => 4,  47 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends '@Installation/layout.twig' %}

{% block content %}

    {% if displayfirstWebsiteSetupSuccess is defined %}
        <div id=\"feedback\" class=\"alert alert-success\">
            {{ 'Installation_SetupWebsiteSetupSuccess'|translate(displaySiteName) }}
        </div>
    {% endif %}

    {{ trackingHelp|raw }}

    <h3>{{ 'Installation_LargePiwikInstances'|translate }}</h3>
    <p>
        {{ 'Installation_JsTagArchivingHelp1'|translate(externallink('https://matomo.org/docs/setup-auto-archiving/'),'</a>')|raw }}
    </p>
    <p>
        {{ 'General_ReadThisToLearnMore'|translate(externallink('https://matomo.org/docs/optimize/'),'</a>')|raw }}
    </p>

{% endblock %}
", "@Installation/trackingCode.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\Installation\\templates\\trackingCode.twig");
    }
}
