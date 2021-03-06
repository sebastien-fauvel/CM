<?php

function smarty_block_form($params, $content, Smarty_Internal_Template $template, $open) {
    /** @var CM_Frontend_Render $render */
    $render = $template->smarty->getTemplateVars('render');
    $frontend = $render->getGlobalResponse();
    if ($open) {
        $form = CM_Form_Abstract::factory($params['name'], $params);
        $viewResponse = new CM_Frontend_ViewResponse($form);
        $form->prepare($render->getEnvironment(), $viewResponse);

        $frontend->treeExpand($viewResponse);
        return '';
    } else {
        $viewResponse = $frontend->getClosestViewResponse('CM_Form_Abstract');
        if (null === $viewResponse) {
            throw new CM_Exception_Invalid('Cannot find `CM_Form_Abstract` within frontend tree.');
        }
        /** @var CM_Form_Abstract $form */
        $form = $viewResponse->getView();

        $cssClasses = $viewResponse->getCssClasses();
        $cssClasses[] = $form->getName();
        $autosave = isset($params['autosave']);
        $html = '<form id="' . $viewResponse->getAutoId() . '" class="' .
            implode(' ', $cssClasses) . '" method="post" action="" onsubmit="return false;" novalidate ' .
            ($autosave ? 'data-autosave="true"' : '') . ' >';
        if ($form->getAvoidPasswordManager()) {
            $html .= '<input style="display:none" type="text" name="fakeusernameremembered">';
            $html .= '<input style="display:none" type="password" name="fakepasswordremembered">';
        }

        $html .= $content;

        foreach ($form->getFields() as $field) {
            if ($field instanceof CM_FormField_Hidden) {
                $renderAdapter = new CM_RenderAdapter_FormField($render, $field);
                $html .= $renderAdapter->fetch(CM_Params::factory());
            }
        }
        $html .= '</form>';

        $frontend->treeCollapse();
        return $html;
    }
}
