<?php

declare(strict_types=1);

namespace Bigfork\Dev\Tasks;

use Page;
use DNADesign\ElementalUserForms\Model\ElementForm;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\UserForms\Model\EditableCustomRule;
use SilverStripe\UserForms\Model\EditableFormField\EditableCheckbox;
use SilverStripe\UserForms\Model\EditableFormField\EditableCheckboxGroupField;
use SilverStripe\UserForms\Model\EditableFormField\EditableDropdown;
use SilverStripe\UserForms\Model\EditableFormField\EditableEmailField;
use SilverStripe\UserForms\Model\EditableFormField\EditableFormHeading;
use SilverStripe\UserForms\Model\EditableFormField\EditableLiteralField;
use SilverStripe\UserForms\Model\EditableFormField\EditableOption;
use SilverStripe\UserForms\Model\EditableFormField\EditableRadioField;
use SilverStripe\UserForms\Model\EditableFormField\EditableTextField;

class CreateTestUserForm extends BuildTask
{
    private static string $segment = 'create-test-user-form';

    protected $title = 'Create Test User Form';

    protected $description = 'Creates a test user form with a mix a of conditional and required fields';

    public function isEnabled(): bool
    {
        if (Director::isDev()) {
            return true;
        }

        return false;
    }

    public function run($request): void
    {
        $pageId = $request->getVar('pageID');
        if (!$pageId) {
            echo 'Please add the ID of the page you want the test form adding to with: ?pageID=YOUR_ID';
            echo '<br /><br /><br />';

            echo "<table><thead><tr><th style=\"text-align: left\">ID</th><th style=\"text-align: left\">Title</th></tr></thead><tbody>";
            $pages = Page::get();
            foreach ($pages as $page) {
                echo "<tr><td>{$page->ID}&nbsp;&nbsp;&nbsp;&nbsp;</td><td>{$page->Title}</td></tr>";
            }

            echo "</tbody></table>";

            return;
        }

        if (!class_exists('DNADesign\ElementalUserForms\Model\ElementForm')) {
            echo 'ElementForm module required';
            return;
        }

        /** @var Page $page */
        $page = Page::get_by_id($pageId);
        if (!$page) {
            echo 'Page does not exist';
            return;
        }

        if (!$page->hasMethod('ElementalArea')) {
            echo 'Elemental area does not exist on this page';
            return;
        }

        $elementForm = ElementForm::create(
            [
                'Title'     => 'Custom form',
                'ParentID'  => $page->ElementalAreaID,
                'TopPageID' => $page->ID,
            ]
        );
        $elementFormID = $elementForm->write();

        EditableTextField::create(
            [
                'Title'         => 'Name',
                'Description'   => 'Please enter your name in this field',
                'Required'      => true,
                'ShowInSummary' => true,
                'ParentID'      => $elementFormID,
                'ParentClass'   => ElementForm::class,
            ]
        )->write();

        $radioButtonGroupId = EditableRadioField::create(
            [
                'Title'         => 'Radio Button Group',
                'Description'   => 'Please select which option best describes a deer, a female deer',
                'Required'      => false,
                'ShowInSummary' => false,
                'ParentID'      => $elementFormID,
                'ParentClass'   => ElementForm::class,
            ]
        )->write();

        foreach (['Do', 'Re', 'Mi'] as $option) {
            EditableOption::create(
                [
                    'Title'    => $option,
                    'Value'    => $option,
                    'ParentID' => $radioButtonGroupId,
                ]
            )->write();
        }

        $checkboxId = EditableCheckbox::create(
            [
                'Title'         => 'Checkbox',
                'Required'      => true,
                'ShowInSummary' => false,
                'ParentID'      => $elementFormID,
                'ParentClass'   => ElementForm::class,
            ]
        )->write();

        $conditionalHeadingId = EditableFormHeading::create(
            [
                'Title'       => 'Conditional Heading',
                'Level'       => 3,
                'ShowOnLoad'  => false,
                'ParentID'    => $elementFormID,
                'ParentClass' => ElementForm::class,
            ]
        )->write();

        $conditionalFieldProperties = [
            'ConditionFieldID' => $checkboxId,
            'ConditionOption'  => 'IsNotBlank',
            'Display'          => 'Hide',
        ];

        EditableCustomRule::create(
            [
                'ParentID' => $conditionalHeadingId,
                ...$conditionalFieldProperties,
            ]
        )->write();

        $conditionalHTMLId = EditableLiteralField::create(
            [
                'Title'           => 'Conditional HTML',
                'Content'         => '<p>You have ticked <strong>the box</strong>.</p>',
                'HideFromReports' => true,
                'ShowOnLoad'      => false,
                'ParentID'        => $elementFormID,
                'ParentClass'     => ElementForm::class,
            ]
        )->write();

        EditableCustomRule::create(
            [
                'ParentID' => $conditionalHTMLId,
                ...$conditionalFieldProperties,
            ]
        )->write();

        $conditionalFieldID = EditableTextField::create(
            [
                'Title'         => 'Conditional Field',
                'Required'      => false,
                'ShowInSummary' => false,
                'ShowOnLoad'    => false,
                'ParentID'      => $elementFormID,
                'ParentClass'   => ElementForm::class,
            ]
        )->write();

        EditableCustomRule::create(
            [
                'ParentID' => $conditionalFieldID,
                ...$conditionalFieldProperties,
            ]
        )->write();

        EditableFormHeading::create(
            [
                'Title' => 'Just a heading within the form',
                'Level' => 3,
            ]
        )->write();

        $checkboxGroupId = EditableCheckboxGroupField::create(
            [
                'Title'         => 'Checkbox Group',
                'Description'   => 'Checkbox description here',
                'Required'      => false,
                'ShowInSummary' => false,
                'ParentID'      => $elementFormID,
                'ParentClass'   => ElementForm::class,
            ]
        )->write();

        foreach (['One', 'Two', 'Three'] as $option) {
            EditableOption::create(
                [
                    'Title'    => $option,
                    'Value'    => $option,
                    'ParentID' => $checkboxGroupId,
                ]
            )->write();
        }

        $checkboxGroupId = EditableDropdown::create(
            [
                'Title'          => 'Dropdown',
                'Required'       => false,
                'ShowInSummary'  => false,
                'ParentID'       => $elementFormID,
                'ParentClass'    => ElementForm::class,
                'UseEmptyString' => true,
                'EmptyString'    => 'Please select',
            ]
        )->write();

        foreach (['Red', 'Green', 'Blue'] as $option) {
            EditableOption::create(
                [
                    'Title'    => $option,
                    'Value'    => $option,
                    'ParentID' => $checkboxGroupId,
                ]
            )->write();
        }

        EditableEmailField::create(
            [
                'Title'         => 'Email',
                'Required'      => true,
                'ShowInSummary' => true,
                'ParentID'      => $elementFormID,
                'ParentClass'   => ElementForm::class,
            ]
        )->write();

        EditableLiteralField::create(
            [
                'Title'           => 'Custom HTML',
                'HTML'            => "<p>This is just some HTML in the form, and we've ticked the box to stop it saving to submissions.</p>",
                'HideFromReports' => true,
            ]
        )->write();

        EditableTextField::create(
            [
                'Title'         => 'Message',
                'Required'      => true,
                'ShowInSummary' => false,
                'ParentID'      => $elementFormID,
                'ParentClass'   => ElementForm::class,
                'Rows'          => 8,
            ]
        )->write();

        $elementForm->publishRecursive();

        echo 'Success!';
    }
}
