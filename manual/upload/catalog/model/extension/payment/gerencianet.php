<?php

/**
 * Classe que gerencia o BD
 */
class ModelExtensionPaymentGerencianet extends Model {
	
    /**
     * Método responsável em mostrar a opção de pagamento no Checkout
     * @param mixed $address
     * @param mixed $total
     * @return array
     */
    public function getMethod($address, $total) {
		$this->load->language('extension/payment/gerencianet');

        $title_show = '<span style="color: #f37021; font-weight: bold; font-size:14px  " >Efí Bank</span>';
        $method_data = array(
            'code'       => 'gerencianet',
            'title'      => $title_show,
            'terms'      => '',
            'sort_order' => ''
        );

		return $method_data;
	}
    
    /**
     * Salva os dados do pedido com a cobrança gerada
     * @param mixed $data
     */
    public function insert($data, $tableName) {
        $tableNameWithPrefix = $this->getTableWithPrefix($tableName);
        
        $columns = implode('`,`', array_keys($data));
        $values = "'" . implode("','", array_values($data)) . "'";
    
        $this->db->query("INSERT INTO `{$tableNameWithPrefix}` (`$columns`) VALUES ($values)");
    }
    

    /**
     * Atualiza os dados do pedido
     * @param string $column
     * @param string $vaule
     */
    public function update($conditions, $dataToUpdate, $tableName){
        $tableNameWithPrefix = $this->getTableWithPrefix($tableName);
        $querySET = $this->getQueryformatted($dataToUpdate);
        $queryWHERE = $this->getQueryformatted($conditions);

        $this->db->query("UPDATE `{$tableNameWithPrefix}` 
                SET $querySET WHERE $queryWHERE");
    }

    /**
     * Faz um SELECT no banco, recebendo a coluna e valor a ser comparado
     * @param string $column
     * @param string $value
     * @return mixed
     */
    public function find($column, $value, $tableName){
        $tableNameWithPrefix = $this->getTableWithPrefix($tableName);
        // Retorna um objeto stdClass       
        $arrayReturn = $this->db->query("SELECT * FROM `{$tableNameWithPrefix}` WHERE `{$column}` = '{$value}'");
        // Retorno apenas o array 'row'
        return $arrayReturn->row;
    }
    
    /**
     * Concatenado o nome da tabela com o Prefix padrão 
     * @param string $table_name
     * @return string
     */
    private function getTableWithPrefix($table_name){
		return DB_PREFIX . $table_name;
	}

    /**
     * Concatena os dados para ser usado na query
     * @param array $data
     * @return string
     */
    private function getQueryformatted($data){
        $query = '';
        foreach ($data as $key => $value) {
            $query .= "`{$key}` = '$value' ,";
        }

        // Remove a última vírgula da query
        return substr($query, 0, -1);
    }
}
