<?php
class Odbc_Controller extends FreePbx_Controller
{
    protected $baseModel = 'Odbc';
    public $writable = array(
        'host',
        'pass',
        'type',
        'user',
        'port',
        'dsn_name',
        'description',
        'database'
    );
    private $odbc = NULL;
    public function index()
    {
        $this->template->content = new View('generic/grid');
        // Build a grid with a hidden device_id, class_type, and add an option for the user to select the display columns
        $this->grid = jgrid::grid($this->baseModel, array(
            'caption' => 'My ODBC Connections',
            'multiselect' => true
        ))->add('odbc_id', 'ID', array(
            'hidden' => true,
            'key' => true
        ))->add('dsn_name', 'DSN Name', array(
            'width' => '60'
        ))->add('description', 'Description', array(
            'width' => '120'
        ))->add('host', 'Host', array(
            'width' => '60'
        ))->add('user', 'User', array(
            'width' => '50'
        ))->add('type', 'Type', array(
            'width' => '30'
        ))->add('port', 'port', array(
            'width' => '30'
        ))->navButtonAdd('Columns', array(
            'onClickButton' => 'function () {  $(\'#{table_id}\').setColumns(); }',
            'buttonimg' => url::base() . 'assets/css/jqGrid/table_insert_column.png',
            'title' => 'Show/Hide Columns',
            'noCaption' => true,
            'position' => 'first'
        ))->addAction('odbc/update', 'Edit', array(
            'arguments' => 'odbc_id',
            'width' => '40'
        ))->addAction('odbc/delete', 'Delete', array(
            'arguments' => 'odbc_id',
            'width' => '40'
        ))->addAction('odbc/config', 'odbc.ini', array(
            'arguments' => 'odbc_id',
            'width' => '60'
        ))->navGrid(array(
            'del' => true
        ));
        // dont foget to let the plugins add to the grid!
        plugins::views($this);
        // Produces the grid markup or JSON, respectively
        $this->view->grid = $this->grid->produce();
    }
    public function update($odbc_id)
    {
        $this->template->content = new View(Router::$controller . '/update');
        $this->view->title = 'Edit Connection';
        $this->odbc = Doctrine::getTable('Odbc')->findOneByOdbcId($odbc_id);
        // Are we supposed to be saving stuff? (received a form post?)
        if ($this->submitted()) {
            $_POST['odbc']['port'] = $this->getPort();
            if ($this->formSave($this->odbc)) {
                url::redirect(Router_Core::$controller);
            }
        }
        // Allow our device object to be seen by the view
        $this->view->odbc = $this->odbc;
        // Execute plugin hooks here, after we've loaded the core data sets
        plugins::views($this);
    }
    public function add()
    {
        $this->template->content = new View(Router::$controller . '/update');
        $this->view->title = 'Add a Connection';
        $this->odbc = new Odbc();
        if ($this->submitted()) {
            $_POST['odbc']['port'] = $this->getPort();
            if ($this->formSave($this->odbc)) {
                url::redirect(Router_Core::$controller . '/update/' . $this->odbc->odbc_id);
            }
        }
        plugins::views($this);
    }
    public function delete($odbc_id)
    {
        $this->stdDelete($odbc_id);
    }
    private function getPort()
    {
        $post = $this->input->post('odbc');
        if ($post) {
            if (strlen($post['port']) == 0) {
                $port = OdbcManager::lookupPort($post['type']);
            } else {
                $port = $post['port'];
            }
        } else {
            $port = 0;
        }
        return $port;
    }
    public function config($odbc_id)
    {
        $this->view->title = 'ODBC Configuration';
        $odbc = Doctrine::getTable('Odbc')->findOneByOdbcId($odbc_id);
        if (!$odbc) {
            throw new Exception("Invalid odbc id");
        }
        $dsnString = "[ODBC Data Sources]\n";
        $dsnString.= "odbcname\t= MyODBC 3.51 Driver DSN\n\n";
        $dsnString.= sprintf("[%s]\n", $odbc->dsn_name);
        $dsnString.= "Driver\t\t= /usr/lib/odbc/libmyodbc.so\n";
        $dsnString.= "Description\t= FreePBX ODBC Connection\n";
        $dsnString.= sprintf("SERVER\t\t= %s\n", $odbc->host);
        $dsnString.= sprintf("PORT\t\t= %d\n", $odbc->port);
        $dsnString.= sprintf("USER\t\t= %s\n", $odbc->user);
        $dsnString.= sprintf("Password\t= %s\n", $odbc->pass);
        $dsnString.= sprintf("Database\t= %s\n", $odbc->database);
        $dsnString.= "OPTION\t\t= 3\n";
        $dsnString.= "SOCKET\t\t=\n";
        $this->view->dsnString = $dsnString;
    }
}
