<?php

namespace Kanboard\Formatter;

use Kanboard\Model\User;
use Kanboard\Core\Filter\FormatterInterface;

/**
 * Auto-complete formatter for user filter
 *
 * @package  formatter
 * @author   Frederic Guillot
 */
class UserAutoCompleteFormatter extends BaseFormatter implements FormatterInterface
{
    /**
     * Format the tasks for the ajax autocompletion
     *
     * @access public
     * @return array
     */
    public function format()
    {
        $users = $this->query->columns(User::TABLE.'.id', User::TABLE.'.username', User::TABLE.'.name')->findAll();

        foreach ($users as &$user) {
            $user['value'] = $user['username'].' (#'.$user['id'].')';

            if (empty($user['name'])) {
                $user['label'] = $user['username'];
            } else {
                $user['label'] = $user['name'].' ('.$user['username'].')';
            }
        }

        return $users;
    }
}
