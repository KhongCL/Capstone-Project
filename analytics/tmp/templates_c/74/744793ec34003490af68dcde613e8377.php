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

/* @TagManager/tagmanager.twig */
class __TwigTemplate_014f1ff47a046923bf3c4c58f56d6b9c extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
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
        // line 3
        $context["categoryTitle"] = ('' === $tmp = "Tag Manager") ? '' : new Markup($tmp, $this->env->getCharset());
        // line 5
        $context["bodyClass"] = $this->env->getFunction('postEvent')->getCallable()("Template.bodyClass", "tagmanager");
        // line 6
        $context["isAdminArea"] = true;
        // line 1
        $this->parent = $this->loadTemplate("layout.twig", "@TagManager/tagmanager.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 8
    public function block_body($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 9
        yield "    ";
        $context["topMenuModule"] = "TagManager";
        // line 10
        yield "    ";
        $context["topMenuAction"] = ((array_key_exists("tagAction", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["tagAction"]) || array_key_exists("tagAction", $context) ? $context["tagAction"] : (function () { throw new RuntimeError('Variable "tagAction" does not exist.', 10, $this->source); })()), "")) : (""));
        // line 11
        yield "    ";
        yield $this->env->getFunction('postEvent')->getCallable()("Template.header", "tagmanager");
        yield "
    ";
        // line 12
        yield from $this->yieldParentBlock("body", $context, $blocks);
        yield "
    ";
        // line 13
        yield $this->env->getFunction('postEvent')->getCallable()("Template.footer", "tagmanager");
        yield "
";
        return; yield '';
    }

    // line 16
    public function block_root($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 17
        yield "    ";
        yield from         $this->loadTemplate("@CoreHome/_topScreen.twig", "@TagManager/tagmanager.twig", 17)->unwrap()->yield($context);
        // line 18
        yield "
<div class=\"top_controls\">
    <div vue-entry=\"CoreHome.QuickAccess\" class=\"piwikTopControl borderedControl\">
    </div>

    ";
        // line 23
        yield from $this->unwrap()->yieldBlock('topcontrols', $context, $blocks);
        // line 41
        yield "
    <span class=\"icon icon-arrowup\"></span>
</div>

";
        // line 45
        $macros["ajax"] = $this->loadTemplate("ajaxMacros.twig", "@TagManager/tagmanager.twig", 45)->unwrap();
        // line 46
        yield "    ";
        yield CoreExtension::callMacro($macros["ajax"], "macro_requestErrorDiv", [((array_key_exists("emailSuperUser", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["emailSuperUser"]) || array_key_exists("emailSuperUser", $context) ? $context["emailSuperUser"] : (function () { throw new RuntimeError('Variable "emailSuperUser" does not exist.', 46, $this->source); })()), "")) : ("")), (isset($context["areAdsForProfessionalServicesEnabled"]) || array_key_exists("areAdsForProfessionalServicesEnabled", $context) ? $context["areAdsForProfessionalServicesEnabled"] : (function () { throw new RuntimeError('Variable "areAdsForProfessionalServicesEnabled" does not exist.', 46, $this->source); })()), (isset($context["currentModule"]) || array_key_exists("currentModule", $context) ? $context["currentModule"] : (function () { throw new RuntimeError('Variable "currentModule" does not exist.', 46, $this->source); })())], 46, $context, $this->getSourceContext());
        yield "
    ";
        // line 47
        yield $this->env->getFunction('postEvent')->getCallable()("Template.beforeContent", "admin", (isset($context["currentModule"]) || array_key_exists("currentModule", $context) ? $context["currentModule"] : (function () { throw new RuntimeError('Variable "currentModule" does not exist.', 47, $this->source); })()), (isset($context["currentAction"]) || array_key_exists("currentAction", $context) ? $context["currentAction"] : (function () { throw new RuntimeError('Variable "currentAction" does not exist.', 47, $this->source); })()));
        yield "

<div class=\"page\">

    ";
        // line 51
        if (( !array_key_exists("showMenu", $context) || (isset($context["showMenu"]) || array_key_exists("showMenu", $context) ? $context["showMenu"] : (function () { throw new RuntimeError('Variable "showMenu" does not exist.', 51, $this->source); })()))) {
            // line 52
            yield "        ";
            $macros["menu"] = $this->loadTemplate("@CoreHome/_menu.twig", "@TagManager/tagmanager.twig", 52)->unwrap();
            // line 53
            yield "        ";
            yield CoreExtension::callMacro($macros["menu"], "macro_menu", [(isset($context["tagManagerMenu"]) || array_key_exists("tagManagerMenu", $context) ? $context["tagManagerMenu"] : (function () { throw new RuntimeError('Variable "tagManagerMenu" does not exist.', 53, $this->source); })()), false, "Menu--admin", (isset($context["currentModule"]) || array_key_exists("currentModule", $context) ? $context["currentModule"] : (function () { throw new RuntimeError('Variable "currentModule" does not exist.', 53, $this->source); })()), (isset($context["currentAction"]) || array_key_exists("currentAction", $context) ? $context["currentAction"] : (function () { throw new RuntimeError('Variable "currentAction" does not exist.', 53, $this->source); })())], 53, $context, $this->getSourceContext());
            yield "
    ";
        }
        // line 55
        yield "
    <div class=\"pageWrap\">
        <a name=\"main\"></a>
        ";
        // line 58
        yield from $this->unwrap()->yieldBlock('notification', $context, $blocks);
        // line 61
        yield "        ";
        yield from         $this->loadTemplate("@CoreHome/_warningInvalidHost.twig", "@TagManager/tagmanager.twig", 61)->unwrap()->yield($context);
        // line 62
        yield "
        <div class=\"admin\" id=\"content\">

            <div class=\"ui-confirm\" id=\"alert\">
                <h2></h2>
                <input role=\"no\" type=\"button\" value=\"";
        // line 67
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("General_Ok"), "html", null, true);
        yield "\"/>
            </div>

            ";
        // line 70
        yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
        // line 72
        yield "
        </div>
    </div>
</div>

";
        return; yield '';
    }

    // line 23
    public function block_topcontrols($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 24
        yield "
        <div class=\"top_bar_sites_selector piwikTopControl\">
            <div
                vue-entry=\"CoreHome.SiteSelector\"
                show-selected-site=\"true\"
                show-all-sites-item=\"false\"
                class=\"sites_autocomplete\"
            ></div>
        </div>

        <div class=\"piwikTopControl\">
            <div
                vue-entry=\"TagManager.ContainerSelector\"
                ";
        // line 37
        if ((array_key_exists("container", $context) &&  !Twig\Extension\CoreExtension::testEmpty((isset($context["container"]) || array_key_exists("container", $context) ? $context["container"] : (function () { throw new RuntimeError('Variable "container" does not exist.', 37, $this->source); })())))) {
            yield "container-name=\"";
            yield \Piwik\piwik_escape_filter($this->env, json_encode((((CoreExtension::getAttribute($this->env, $this->source, (isset($context["container"]) || array_key_exists("container", $context) ? $context["container"] : (function () { throw new RuntimeError('Variable "container" does not exist.', 37, $this->source); })()), "name", [], "any", false, false, false, 37) . " (") . CoreExtension::getAttribute($this->env, $this->source, (isset($context["container"]) || array_key_exists("container", $context) ? $context["container"] : (function () { throw new RuntimeError('Variable "container" does not exist.', 37, $this->source); })()), "idcontainer", [], "any", false, false, false, 37)) . ")")), "html", null, true);
            yield "\"";
        }
        // line 38
        yield "            ></div>
        </div>
    ";
        return; yield '';
    }

    // line 58
    public function block_notification($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 59
        yield "            ";
        yield from         $this->loadTemplate("@CoreHome/_notifications.twig", "@TagManager/tagmanager.twig", 59)->unwrap()->yield($context);
        // line 60
        yield "        ";
        return; yield '';
    }

    // line 70
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
        return "@TagManager/tagmanager.twig";
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
        return array (  210 => 70,  205 => 60,  202 => 59,  198 => 58,  191 => 38,  185 => 37,  170 => 24,  166 => 23,  156 => 72,  154 => 70,  148 => 67,  141 => 62,  138 => 61,  136 => 58,  131 => 55,  125 => 53,  122 => 52,  120 => 51,  113 => 47,  108 => 46,  106 => 45,  100 => 41,  98 => 23,  91 => 18,  88 => 17,  84 => 16,  77 => 13,  73 => 12,  68 => 11,  65 => 10,  62 => 9,  58 => 8,  53 => 1,  51 => 6,  49 => 5,  47 => 3,  40 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends 'layout.twig' %}

{% set categoryTitle %}Tag Manager{% endset %}

{% set bodyClass = postEvent('Template.bodyClass', 'tagmanager') %}
{% set isAdminArea = true %}

{% block body %}
    {% set topMenuModule = 'TagManager' %}
    {% set topMenuAction = tagAction|default('') %}
    {{ postEvent(\"Template.header\", \"tagmanager\") }}
    {{ parent() }}
    {{ postEvent(\"Template.footer\", \"tagmanager\") }}
{% endblock %}

{% block root %}
    {% include \"@CoreHome/_topScreen.twig\" %}

<div class=\"top_controls\">
    <div vue-entry=\"CoreHome.QuickAccess\" class=\"piwikTopControl borderedControl\">
    </div>

    {% block topcontrols %}

        <div class=\"top_bar_sites_selector piwikTopControl\">
            <div
                vue-entry=\"CoreHome.SiteSelector\"
                show-selected-site=\"true\"
                show-all-sites-item=\"false\"
                class=\"sites_autocomplete\"
            ></div>
        </div>

        <div class=\"piwikTopControl\">
            <div
                vue-entry=\"TagManager.ContainerSelector\"
                {% if container is defined and container is not empty %}container-name=\"{{ (container.name ~ ' (' ~ container.idcontainer ~ ')')|json_encode }}\"{% endif %}
            ></div>
        </div>
    {% endblock %}

    <span class=\"icon icon-arrowup\"></span>
</div>

{% import 'ajaxMacros.twig' as ajax %}
    {{ ajax.requestErrorDiv(emailSuperUser|default(''), areAdsForProfessionalServicesEnabled, currentModule) }}
    {{ postEvent(\"Template.beforeContent\", \"admin\", currentModule, currentAction) }}

<div class=\"page\">

    {% if showMenu is not defined or showMenu %}
        {% import '@CoreHome/_menu.twig' as menu %}
        {{ menu.menu(tagManagerMenu, false, 'Menu--admin', currentModule, currentAction) }}
    {% endif %}

    <div class=\"pageWrap\">
        <a name=\"main\"></a>
        {% block notification %}
            {% include \"@CoreHome/_notifications.twig\" %}
        {% endblock %}
        {% include \"@CoreHome/_warningInvalidHost.twig\" %}

        <div class=\"admin\" id=\"content\">

            <div class=\"ui-confirm\" id=\"alert\">
                <h2></h2>
                <input role=\"no\" type=\"button\" value=\"{{ 'General_Ok'|translate }}\"/>
            </div>

            {% block content %}
            {% endblock %}

        </div>
    </div>
</div>

{% endblock %}
", "@TagManager/tagmanager.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\TagManager\\templates\\tagmanager.twig");
    }
}
