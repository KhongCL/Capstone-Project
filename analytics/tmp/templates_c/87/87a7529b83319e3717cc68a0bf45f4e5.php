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

/* @Installation/firstWebsiteSetup.twig */
class __TwigTemplate_a3c0c16a4e54eeec0bf8568f86532fbb extends Template
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
        $this->parent = $this->loadTemplate("@Installation/layout.twig", "@Installation/firstWebsiteSetup.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        yield "
    <h2>";
        // line 5
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_SetupWebsite"), "html", null, true);
        yield "</h2>

    ";
        // line 7
        if (array_key_exists("displayGeneralSetupSuccess", $context)) {
            // line 8
            yield "        <div id=\"feedback\" class=\"alert alert-success\">
            ";
            // line 9
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_SuperUserSetupSuccess"), "html", null, true);
            yield "
        </div>
    ";
        }
        // line 12
        yield "
    <p>";
        // line 13
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_SiteSetup"), "html", null, true);
        yield "</p>

    ";
        // line 15
        if (array_key_exists("errorMessage", $context)) {
            // line 16
            yield "        <div class=\"alert alert-danger\">
            ";
            // line 17
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_SetupWebsiteError"), "html", null, true);
            yield ":
            <br/>- ";
            // line 18
            yield (isset($context["errorMessage"]) || array_key_exists("errorMessage", $context) ? $context["errorMessage"] : (function () { throw new RuntimeError('Variable "errorMessage" does not exist.', 18, $this->source); })());
            yield "
        </div>
    ";
        }
        // line 21
        yield "
    ";
        // line 22
        if (array_key_exists("form_data", $context)) {
            // line 23
            yield "        ";
            yield from             $this->loadTemplate("genericForm.twig", "@Installation/firstWebsiteSetup.twig", 23)->unwrap()->yield($context);
            // line 24
            yield "    ";
        }
        // line 25
        yield "    <div class=\"clearfix\"></div>

    <p><em>";
        // line 27
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_SiteSetupFootnote"), "html", null, true);
        yield "</em></p>

";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@Installation/firstWebsiteSetup.twig";
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
        return array (  108 => 27,  104 => 25,  101 => 24,  98 => 23,  96 => 22,  93 => 21,  87 => 18,  83 => 17,  80 => 16,  78 => 15,  73 => 13,  70 => 12,  64 => 9,  61 => 8,  59 => 7,  54 => 5,  51 => 4,  47 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends '@Installation/layout.twig' %}

{% block content %}

    <h2>{{ 'Installation_SetupWebsite'|translate }}</h2>

    {% if displayGeneralSetupSuccess is defined %}
        <div id=\"feedback\" class=\"alert alert-success\">
            {{ 'Installation_SuperUserSetupSuccess'|translate }}
        </div>
    {% endif %}

    <p>{{ 'Installation_SiteSetup'|translate }}</p>

    {% if errorMessage is defined %}
        <div class=\"alert alert-danger\">
            {{ 'Installation_SetupWebsiteError'|translate }}:
            <br/>- {{ errorMessage|raw }}
        </div>
    {% endif %}

    {% if form_data is defined %}
        {% include \"genericForm.twig\" %}
    {% endif %}
    <div class=\"clearfix\"></div>

    <p><em>{{ 'Installation_SiteSetupFootnote'|translate }}</em></p>

{% endblock %}
", "@Installation/firstWebsiteSetup.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\Installation\\templates\\firstWebsiteSetup.twig");
    }
}
