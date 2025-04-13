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

/* @Installation/finished.twig */
class __TwigTemplate_ffbfbdf8dc21336b8659044290d731a7 extends Template
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
        $this->parent = $this->loadTemplate("@Installation/layout.twig", "@Installation/finished.twig", 1);
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
        yield $this->env->getFilter('translate')->getCallable()("Installation_Congratulations");
        yield "</h2>

    ";
        // line 7
        yield $this->env->getFilter('translate')->getCallable()("Installation_CongratulationsHelp");
        yield "

    <h3>";
        // line 9
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_WelcomeToCommunity"), "html", null, true);
        yield "</h3>
    <p>
        ";
        // line 11
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_CollaborativeProject"), "html", null, true);
        yield "
    </p>
    <p>
        ";
        // line 14
        yield $this->env->getFilter('translate')->getCallable()("Installation_GetInvolved", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/get-involved/"), "</a>");
        yield "
        ";
        // line 15
        yield $this->env->getFilter('translate')->getCallable()("General_HelpTranslatePiwik", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/translations/"), "</a>");
        yield "
    </p>
    <p>";
        // line 17
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_WeHopeYouWillEnjoyPiwik"), "html", null, true);
        yield "</p>
    <p><i>";
        // line 18
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_HappyAnalysing"), "html", null, true);
        yield "</i></p>

    ";
        // line 20
        if ((isset($context["areAdsForProfessionalServicesEnabled"]) || array_key_exists("areAdsForProfessionalServicesEnabled", $context) ? $context["areAdsForProfessionalServicesEnabled"] : (function () { throw new RuntimeError('Variable "areAdsForProfessionalServicesEnabled" does not exist.', 20, $this->source); })())) {
            // line 21
            yield "        <h3>";
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_ProfessionalServicesAdTitle"), "html", null, true);
            yield "</h3>
        <p>
            ";
            // line 23
            yield $this->env->getFilter('translate')->getCallable()("Installation_ProfessionalServicesfessionalServicesAdText", $this->env->getFunction('externallink')->getCallable()("https://plugins.matomo.org/premium"), "</a>", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/support-plans/"));
            yield "
        </p>
        <p>
            ";
            // line 26
            yield $this->env->getFilter('translate')->getCallable()("Installation_ProfessionalServicesfessionalDiscoverHow", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/matomo-cloud/"), "</a>");
            yield "
        </p>
    ";
        }
        // line 29
        yield "
    <h3>Performance Settings</h3>
    <p>";
        // line 31
        yield $this->env->getFilter('translate')->getCallable()("Installation_PerformanceSettingsDesc1", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/docs/setup-auto-archiving/"), "</a>");
        yield "</p>
    <p>";
        // line 32
        yield $this->env->getFilter('translate')->getCallable()("Installation_PerformanceSettingsDesc2", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/docs/setup-auto-archiving/"), "</a>");
        yield "</p>

    <h3>";
        // line 34
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_DefaultSettings"), "html", null, true);
        yield "</h3>
    <p>";
        // line 35
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_DefaultSettingsHelp"), "html", null, true);
        yield "</p>

    ";
        // line 37
        if (array_key_exists("errorMessage", $context)) {
            // line 38
            yield "        <div class=\"alert alert-danger\">
            ";
            // line 39
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("General_Error"), "html", null, true);
            yield ":
            <br/>- ";
            // line 40
            yield (isset($context["errorMessage"]) || array_key_exists("errorMessage", $context) ? $context["errorMessage"] : (function () { throw new RuntimeError('Variable "errorMessage" does not exist.', 40, $this->source); })());
            yield "
        </div>
    ";
        }
        // line 43
        yield "
    <div class=\"installation-finished\">
        ";
        // line 45
        if (array_key_exists("form_data", $context)) {
            // line 46
            yield "            ";
            yield from             $this->loadTemplate("genericForm.twig", "@Installation/finished.twig", 46)->unwrap()->yield($context);
            // line 47
            yield "        ";
        }
        // line 48
        yield "    </div>

";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@Installation/finished.twig";
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
        return array (  162 => 48,  159 => 47,  156 => 46,  154 => 45,  150 => 43,  144 => 40,  140 => 39,  137 => 38,  135 => 37,  130 => 35,  126 => 34,  121 => 32,  117 => 31,  113 => 29,  107 => 26,  101 => 23,  95 => 21,  93 => 20,  88 => 18,  84 => 17,  79 => 15,  75 => 14,  69 => 11,  64 => 9,  59 => 7,  54 => 5,  51 => 4,  47 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends '@Installation/layout.twig' %}

{% block content %}

    <h2>{{ 'Installation_Congratulations'|translate|raw }}</h2>

    {{ 'Installation_CongratulationsHelp'|translate|raw }}

    <h3>{{ 'Installation_WelcomeToCommunity'|translate }}</h3>
    <p>
        {{ 'Installation_CollaborativeProject'|translate }}
    </p>
    <p>
        {{ 'Installation_GetInvolved'|translate(externallink('https://matomo.org/get-involved/'),'</a>')|raw }}
        {{ 'General_HelpTranslatePiwik'|translate(externallink('https://matomo.org/translations/'),\"<\\/a>\")|raw }}
    </p>
    <p>{{ 'Installation_WeHopeYouWillEnjoyPiwik'|translate }}</p>
    <p><i>{{ 'Installation_HappyAnalysing'|translate }}</i></p>

    {% if areAdsForProfessionalServicesEnabled %}
        <h3>{{ 'Installation_ProfessionalServicesAdTitle'|translate }}</h3>
        <p>
            {{ 'Installation_ProfessionalServicesfessionalServicesAdText'|translate(externallink('https://plugins.matomo.org/premium'),'</a>',externallink('https://matomo.org/support-plans/'))|raw }}
        </p>
        <p>
            {{ 'Installation_ProfessionalServicesfessionalDiscoverHow'|translate(externallink('https://matomo.org/matomo-cloud/'),'</a>')|raw }}
        </p>
    {% endif %}

    <h3>Performance Settings</h3>
    <p>{{ 'Installation_PerformanceSettingsDesc1'|translate(externallink('https://matomo.org/docs/setup-auto-archiving/'), '</a>')|raw }}</p>
    <p>{{ 'Installation_PerformanceSettingsDesc2'|translate(externallink('https://matomo.org/docs/setup-auto-archiving/'), '</a>')|raw }}</p>

    <h3>{{ 'Installation_DefaultSettings'|translate }}</h3>
    <p>{{ 'Installation_DefaultSettingsHelp'|translate }}</p>

    {% if errorMessage is defined %}
        <div class=\"alert alert-danger\">
            {{ 'General_Error'|translate }}:
            <br/>- {{ errorMessage|raw }}
        </div>
    {% endif %}

    <div class=\"installation-finished\">
        {% if form_data is defined %}
            {% include \"genericForm.twig\" %}
        {% endif %}
    </div>

{% endblock %}
", "@Installation/finished.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\Installation\\templates\\finished.twig");
    }
}
