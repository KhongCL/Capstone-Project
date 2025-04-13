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

/* @ScheduledReports/reportParametersScheduledReports.twig */
class __TwigTemplate_1987272d7f8bc36ff90b6b6de7976ac0 extends Template
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
  vue-entry=\"ScheduledReports.ReportParameters\"
  report-type=\"";
        // line 3
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("reportType", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["reportType"]) || array_key_exists("reportType", $context) ? $context["reportType"] : (function () { throw new RuntimeError('Variable "reportType" does not exist.', 3, $this->source); })()), null)) : (null))), "html_attr");
        yield "\"
  default-display-format=\"";
        // line 4
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("defaultDisplayFormat", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["defaultDisplayFormat"]) || array_key_exists("defaultDisplayFormat", $context) ? $context["defaultDisplayFormat"] : (function () { throw new RuntimeError('Variable "defaultDisplayFormat" does not exist.', 4, $this->source); })()), null)) : (null))), "html_attr");
        yield "\"
  default-email-me=\"";
        // line 5
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("defaultEmailMe", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["defaultEmailMe"]) || array_key_exists("defaultEmailMe", $context) ? $context["defaultEmailMe"] : (function () { throw new RuntimeError('Variable "defaultEmailMe" does not exist.', 5, $this->source); })()), false)) : (false))), "html_attr");
        yield "\"
  default-evolution-graph=\"";
        // line 6
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("defaultEvolutionGraph", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["defaultEvolutionGraph"]) || array_key_exists("defaultEvolutionGraph", $context) ? $context["defaultEvolutionGraph"] : (function () { throw new RuntimeError('Variable "defaultEvolutionGraph" does not exist.', 6, $this->source); })()), false)) : (false))), "html_attr");
        yield "\"
  current-user-email=\"";
        // line 7
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("currentUserEmail", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["currentUserEmail"]) || array_key_exists("currentUserEmail", $context) ? $context["currentUserEmail"] : (function () { throw new RuntimeError('Variable "currentUserEmail" does not exist.', 7, $this->source); })()), null)) : (null))), "html_attr");
        yield "\"
></div>";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@ScheduledReports/reportParametersScheduledReports.twig";
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
        return array (  58 => 7,  54 => 6,  50 => 5,  46 => 4,  42 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<div
  vue-entry=\"ScheduledReports.ReportParameters\"
  report-type=\"{{ reportType|default(null)|json_encode|e('html_attr') }}\"
  default-display-format=\"{{ defaultDisplayFormat|default(null)|json_encode|e('html_attr') }}\"
  default-email-me=\"{{ defaultEmailMe|default(false)|json_encode|e('html_attr') }}\"
  default-evolution-graph=\"{{ defaultEvolutionGraph|default(false)|json_encode|e('html_attr') }}\"
  current-user-email=\"{{ currentUserEmail|default(null)|json_encode|e('html_attr') }}\"
></div>", "@ScheduledReports/reportParametersScheduledReports.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\ScheduledReports\\templates\\reportParametersScheduledReports.twig");
    }
}
