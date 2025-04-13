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

/* @Installation/setupSuperUser.twig */
class __TwigTemplate_fedff0f3ba2c724b0c0cb0ad9fb87aeb extends Template
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
        $this->parent = $this->loadTemplate("@Installation/layout.twig", "@Installation/setupSuperUser.twig", 1);
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
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_SuperUser"), "html", null, true);
        yield "</h2>

    ";
        // line 7
        if (array_key_exists("errorMessage", $context)) {
            // line 8
            yield "        <div class=\"alert alert-danger\">
            ";
            // line 9
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_SuperUserSetupError"), "html", null, true);
            yield ":
            <br/>- ";
            // line 10
            yield (isset($context["errorMessage"]) || array_key_exists("errorMessage", $context) ? $context["errorMessage"] : (function () { throw new RuntimeError('Variable "errorMessage" does not exist.', 10, $this->source); })());
            yield "
        </div>
    ";
        }
        // line 13
        yield "
    ";
        // line 14
        if (array_key_exists("form_data", $context)) {
            // line 15
            yield "        ";
            yield from             $this->loadTemplate("genericForm.twig", "@Installation/setupSuperUser.twig", 15)->unwrap()->yield($context);
            // line 16
            yield "    ";
        }
        // line 17
        yield "
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@Installation/setupSuperUser.twig";
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
        return array (  85 => 17,  82 => 16,  79 => 15,  77 => 14,  74 => 13,  68 => 10,  64 => 9,  61 => 8,  59 => 7,  54 => 5,  51 => 4,  47 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends '@Installation/layout.twig' %}

{% block content %}

    <h2>{{ 'Installation_SuperUser'|translate }}</h2>

    {% if errorMessage is defined %}
        <div class=\"alert alert-danger\">
            {{ 'Installation_SuperUserSetupError'|translate }}:
            <br/>- {{ errorMessage|raw }}
        </div>
    {% endif %}

    {% if form_data is defined %}
        {% include \"genericForm.twig\" %}
    {% endif %}

{% endblock %}
", "@Installation/setupSuperUser.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\Installation\\templates\\setupSuperUser.twig");
    }
}
