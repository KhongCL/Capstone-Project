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

/* @TagManager/manageTags.twig */
class __TwigTemplate_c28eededb7caea8d06c5d66e66dffc61 extends Template
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
        $context["title"] = $this->env->getFilter('translate')->getCallable()("TagManager_Tags");
        // line 1
        $this->parent = $this->loadTemplate("@TagManager/tagmanager.twig", "@TagManager/manageTags.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 5
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        yield "    <div
        vue-entry=\"TagManager.TagManage\"
        id-container=\"";
        // line 8
        yield \Piwik\piwik_escape_filter($this->env, json_encode(CoreExtension::getAttribute($this->env, $this->source, (isset($context["container"]) || array_key_exists("container", $context) ? $context["container"] : (function () { throw new RuntimeError('Variable "container" does not exist.', 8, $this->source); })()), "idcontainer", [], "any", false, false, false, 8)), "html", null, true);
        yield "\"
        id-container-version=\"";
        // line 9
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["idcontainerversion"]) || array_key_exists("idcontainerversion", $context) ? $context["idcontainerversion"] : (function () { throw new RuntimeError('Variable "idcontainerversion" does not exist.', 9, $this->source); })())), "html", null, true);
        yield "\"
        tags-help-text=\"";
        // line 10
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["tagsHelpText"]) || array_key_exists("tagsHelpText", $context) ? $context["tagsHelpText"] : (function () { throw new RuntimeError('Variable "tagsHelpText" does not exist.', 10, $this->source); })())), "html", null, true);
        yield "\"
    ></div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@TagManager/manageTags.twig";
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
        return array (  66 => 10,  62 => 9,  58 => 8,  54 => 6,  50 => 5,  45 => 1,  43 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends '@TagManager/tagmanager.twig' %}

{% set title = 'TagManager_Tags'|translate %}

{% block content %}
    <div
        vue-entry=\"TagManager.TagManage\"
        id-container=\"{{ container.idcontainer|json_encode }}\"
        id-container-version=\"{{ idcontainerversion|json_encode }}\"
        tags-help-text=\"{{ tagsHelpText|json_encode }}\"
    ></div>
{% endblock %}", "@TagManager/manageTags.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\TagManager\\templates\\manageTags.twig");
    }
}
