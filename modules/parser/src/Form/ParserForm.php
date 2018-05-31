<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 030 30.05.18
 * Time: 12:40
 */

/**
 * @file
 * Contains Drupal\parser\Form\MessagesForm.
 */

namespace Drupal\parser\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class ParserForm extends ConfigFormBase
{
    protected function getEditableConfigNames()
    {
        return ['parser.adminsettings', ];
    }

    public function getFormId()
    {
        return 'parser_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('parser.adminsettings');

        $form['parser_btn'] = [
            '#type' => 'submit',
            '#value' => $this->t('Parse')
        ];

        return $form;
    }

    public function add_node($id, $title, $text)
    {
        $node = Node::create(['type' => 'article']);
        $node->set('title', $title);

        //Body can now be an array with a value and a format.
        //If body field exists.
        $body = [
            'value' => $text,
            'format' => 'basic_html',
        ];
        $node->set('nid', $id);
        $node->set('body', $body);
        $node->status = 1;
        $node->enforceIsNew();
        $node->save();
    }

    public function get_guid($guid)
    {
        $id = substr(strstr($guid, '='), 1, strlen($guid));
        return $id;
    }

    /**
     * @return int
     */
    public function parse()
    {
        $connection = \Drupal::database();
        $latestPost = $connection->query("SELECT MAX(nid) FROM {node} WHERE type = 'article'")->fetchColumn();
        $url = 'http://bitcoin-zone.ru/feed/';
        $rss = simplexml_load_file($url);
        if($latestPost != $this->get_guid($item = $rss->channel->item[0]->guid))
        {
            $item = $rss->channel->item;
            for($i = count($item) - 1; $i >= 0; $i--)
            {
                $guid = substr(strstr($item[$i]->guid, '='), 1, strlen($item[$i]->guid));
                $title = $item[$i]->title;
                $tmp = $item[$i]->children('http://purl.org/rss/1.0/modules/content/');
                $content = (string)$tmp->encoded;
                $this->add_node($guid, $title, $content);
            }
            return count($item);
        }
        else return 0;
    }

    /**
     * Implements hook_cron().
     */
    function hook_cron()
    {
        $this->parse();
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);
        $add_count = $this->parse();
        drupal_set_message("$add_count nodes created.");
        $this->config('parser.adminsettings')->set('parser_message', $form_state->getValue('parser_message)'))->save();
    }

}