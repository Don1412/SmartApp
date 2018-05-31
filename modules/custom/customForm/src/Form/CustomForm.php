<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 029 29.05.18
 * Time: 11:01
 */

/**
 * @file
 * Contains Drupal\customForm\Form\MailForm.
 */

namespace Drupal\customForm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CustomForm extends FormBase {
    protected function getEditableConfigNames()
    {
        return [
            'customForm.adminsettings',
        ];
    }

    public function getFormId()
    {
        return 'customForm_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['customForm_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('First Name'),
            '#required' => TRUE
        ];

        $form['customForm_lastname'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Last Name'),
            '#required' => TRUE
        ];

        $form['customForm_subject'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Subject'),
            '#required' => TRUE
        ];

        $form['customForm_message'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Message'),
            '#required' => TRUE
        ];

        $form['customForm_email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE
        ];

        $form['customForm_submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send')
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        function valid_email_address($mail) {
            return \Drupal::service('email.validator')
                ->isValid($mail);
        }

        if(!filter_var($form_state->getValue('customForm_email'), FILTER_VALIDATE_EMAIL))
            $form_state->setErrorByName('customForm_email', $this->t("Invalid email"));
    }

    /*public function customForm_entity_insert(\Drupal\Core\Entity\EntityInterface $entity) {
        if ($entity->getEntityTypeId() !== 'node' || ($entity->getEntityTypeId() === 'node' && $entity->bundle() !== 'article')) {
            return;
        }
        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = ‘customForm’;
        $key = 'create_article';
        $to = \Drupal::currentUser()->getEmail();
        $params['message'] = $entity->get('body')->value;
        $params['node_title'] = $entity->label();
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = true;
        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
        if ($result['result'] !== true) {
            drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
        }
        else {
            drupal_set_message(t('Your message has been sent.'));
        }
    }

    public function customForm_mail($key, &$message, $params) {
        $options = array(
            'langcode' => $message['langcode'],
        );
        switch ($key) {
            case 'create_article':
                $message['from'] = \Drupal::config('system.site')->get('mail');
                $message['subject'] = t('Article created: @title', array('@title' => $params['node_title']), $options);
                $message['body'][] = $params['message'];
                break;
        }
    }*/

    public function create_contact($email, $firstname, $lastname)
    {
        $arr = array(
            'properties' => array(
                array(
                    'property' => 'email',
                    'value' => $email
                ),
                array(
                    'property' => 'firstname',
                    'value' => $firstname
                ),
                array(
                    'property' => 'lastname',
                    'value' => $lastname
                )
            )
        );
        $post_json = json_encode($arr);
        $hapikey = "demo";
        $endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=' . $hapikey;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
        if($status_code != 200)
        {
            drupal_set_message("curl Errors: " . $curl_errors, "error");
            drupal_set_message("\nStatus code: " . $status_code, "error");
            drupal_set_message("\nResponse: " . $response, "error");
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $to = $form_state->getValue('customForm_email');
        $subject = $form_state->getValue('customForm_subject');
        $message = $form_state->getValue('customForm_message');
        $firstname = $form_state->getValue('customForm_firstname');
        $lastname = $form_state->getValue('customForm_lastname');

        $headers = 'From: Don1412@example.com';
        $this->create_contact($to, $firstname, $lastname);
        $send = mail($to, $subject, $message, $headers);
        //hook_mail('customForm',$message, array());
        if($send)
        {
            $res = "Message sent to $to";
            drupal_set_message($res);
            \Drupal::logger('customForm')->notice($res);
        }
        else drupal_set_message("Error sending message","error");
    }
}