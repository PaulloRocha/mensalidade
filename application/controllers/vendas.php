<?php

class Vendas extends CI_Controller {
    
    function __construct() {
        parent::__construct();
                if((!$this->session->userdata('session_id')) || (!$this->session->userdata('logado'))) {
                    redirect('mapos/login');
                }
		
		$this->load->helper(array('form','codegen_helper'));
		$this->load->model('vendas_model','',TRUE);
		$this->data['menuVendas'] = 'Vendas';
	}	
	
	function index(){
		$this->gerenciar();
	}

	function gerenciar(){
        $this->data['usuario'] = $this->vendas_model->getByIdLogado($this->session->userdata('id'));
        $this->load->library('pagination');
        
        
        $config['base_url'] = base_url().'index.php/vendas/gerenciar/';
        $config['total_rows'] = $this->vendas_model->count('vendas');
        $config['per_page'] = 20;
        $config['next_link'] = 'Próxima';
        $config['prev_link'] = 'Anterior';
        $config['full_tag_open'] = '<div class="pagination alternate"><ul>';
        $config['full_tag_close'] = '</ul></div>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li><a style="color: #2D335B"><b>';
        $config['cur_tag_close'] = '</b></a></li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['first_link'] = 'Primeira';
        $config['last_link'] = 'Última';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        	
        $this->pagination->initialize($config); 	

		$this->data['results'] = $this->vendas_model->get('vendas','*','',$config['per_page'],$this->uri->segment(3));
       
	    $this->data['view'] = 'vendas/vendas';
       	$this->load->view('tema/topo',$this->data);
      
		
    }
	
    function adicionar(){
        $this->data['usuario'] = $this->vendas_model->getByIdLogado($this->session->userdata('id'));

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';
        
        if ($this->form_validation->run('vendas') == false) {
           $this->data['custom_error'] = (validation_errors() ? true : false);
        } else {

            $dataVenda = $this->input->post('dataVenda');

            try {
                
                $dataVenda = explode('/', $dataVenda);
                $dataVenda = $dataVenda[2].'-'.$dataVenda[1].'-'.$dataVenda[0];


            } catch (Exception $e) {
               $dataVenda = date('Y/m/d'); 
            }

            $data = array(
                'dataVenda' => $dataVenda,
                'clientes_id' => $this->input->post('clientes_id'),
                'usuarios_id' => $this->input->post('usuarios_id'),
                'faturado' => 0
            );

            if (is_numeric($id = $this->vendas_model->add('vendas', $data, true)) ) {
                $this->session->set_flashdata('success','Venda iniciada com sucesso, adicione os produtos.');
                redirect('vendas/editar/'.$id);

            } else {
                
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro.</p></div>';
            }
        }
         
        $this->data['view'] = 'vendas/adicionarVenda';
        $this->load->view('tema/topo', $this->data);
    }
 
    function editar() {
        
        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('vendas') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {

            $dataVenda = $this->input->post('dataVenda');

            try {
                
                $dataVenda = explode('/', $dataVenda);
                $dataVenda = $dataVenda[2].'-'.$dataVenda[1].'-'.$dataVenda[0];


            } catch (Exception $e) {
               $dataVenda = date('Y/m/d'); 
            }

            $data = array(
                'dataVenda' => $dataVenda,
                'usuarios_id' => $this->input->post('usuarios_id'),
                'clientes_id' => $this->input->post('clientes_id')
            );

            if ($this->vendas_model->edit('vendas', $data, 'idVendas', $this->input->post('idVendas')) == TRUE) {
                $this->session->set_flashdata('success','Venda editada com sucesso!');
                redirect(base_url() . 'index.php/vendas/editar/'.$this->input->post('idVendas'));
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro</p></div>';
            }
        }

        $this->data['result'] = $this->vendas_model->getById($this->uri->segment(3));
        $this->data['produtos'] = $this->vendas_model->getProdutos($this->uri->segment(3));
        $this->data['view'] = 'vendas/editarVenda';
        $this->load->view('tema/topo', $this->data);
   
    }

    public function visualizar(){
        $this->data['custom_error'] = '';
        $this->load->model('mapos_model');
        $this->data['result'] = $this->vendas_model->getById($this->uri->segment(3));
        $this->data['produtos'] = $this->vendas_model->getProdutos($this->uri->segment(3));
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        
        $this->data['view'] = 'vendas/visualizarVenda';
        $this->load->view('tema/topo', $this->data);
       
    }
	
    function excluir(){

        if($this->session->userdata('nivel') != 1){
            $this->session->set_flashdata('error','Você não tem permissão para essa ação.');
            redirect('mapos');
        }
        
        $id =  $this->input->post('id');
        if ($id == null){

            $this->session->set_flashdata('error','Erro ao tentar excluir venda.');            
            redirect(base_url().'index.php/vendas/gerenciar/');
        }

        $this->db->where('vendas_id', $id);
        $this->db->delete('itens_de_vendas');

        $this->db->where('idVendas', $id);
        $this->db->delete('vendas');           

        $this->session->set_flashdata('success','Venda excluída com sucesso!');            
        redirect(base_url().'index.php/vendas/gerenciar/');

    }

    public function autoCompleteProduto(){
        
        if (isset($_GET['term'])){
            $q = strtolower($_GET['term']);
            $this->vendas_model->autoCompleteProduto($q);
        }

    }    
    
    public function autoCompleteProdutocod(){
        if (isset($_GET['term'])){
            $q = strtolower($_GET['term']);
            $this->vendas_model->autoCompleteProdutocod($q);
        }

    }

    public function autoCompleteCliente(){

        if (isset($_GET['term'])){
            $q = strtolower($_GET['term']);
            $this->vendas_model->autoCompleteCliente($q);
        }

    }

    public function autoCompleteUsuario(){

        if (isset($_GET['term'])){
            $q = strtolower($_GET['term']);
            $this->vendas_model->autoCompleteUsuario($q);
        }

    }

    public function adicionarProduto(){

        $this->load->library('form_validation');
        $this->form_validation->set_rules('quantidade', 'Quantidade', 'trim|required|xss_clean');
        $this->form_validation->set_rules('idProduto', 'Produto', 'trim|required|xss_clean');
        $this->form_validation->set_rules('idVendasProduto', 'Vendas', 'trim|required|xss_clean');
        
        if($this->form_validation->run() == false){
           echo json_encode(array('result'=> false)); 
        }
        else{

            $preco = $this->input->post('preco');
            $quantidade = $this->input->post('quantidade');
            $subtotal = $preco * $quantidade;
            $produto = $this->input->post('idProduto');
            $data = array(
                'quantidade'=> $quantidade,
                'subTotal'=> $subtotal,
                'produtos_id'=> $produto,
                'vendas_id'=> $this->input->post('idVendasProduto'),
            );

            if($this->vendas_model->add('itens_de_vendas', $data) == true){
                $sql = "UPDATE produtos set estoque = estoque - ? WHERE idProdutos = ?";
                $this->db->query($sql, array($quantidade, $produto));
                
                echo json_encode(array('result'=> true));
            }else{
                echo json_encode(array('result'=> false));
            }

        }
    }

    function excluirProduto(){
            $ID = $this->input->post('idProduto');
            if($this->vendas_model->delete('itens_de_vendas','idItens',$ID) == true){
                
                $quantidade = $this->input->post('quantidade');
                $produto = $this->input->post('produto');


                $sql = "UPDATE produtos set estoque = estoque + ? WHERE idProdutos = ?";

                $this->db->query($sql, array($quantidade, $produto));
                
                echo json_encode(array('result'=> true));
            }
            else{
                echo json_encode(array('result'=> false));
            }           
    }

    public function faturar() {
    	
        $this->load->library('form_validation');
        $this->data['custom_error'] = '';
 

        if ($this->form_validation->run('receita') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {


            $vencimento = $this->input->post('vencimento');

            try {
                
                $vencimento = explode('/', $vencimento);
                $vencimento = $vencimento[2].'-'.$vencimento[1].'-'.$vencimento[0];

            } catch (Exception $e) {
               $vencimento = date('Y/m/d'); 
            }

            $data = array(
                'descricao' => set_value('descricao'),
                'valor' => $this->input->post('valor'),
                'clientes_id' => $this->input->post('clientes_id'),
                'data_vencimento' => $vencimento,
                'baixado' => $this->input->post('recebido'),
                'cliente_fornecedor' => set_value('cliente'),
                'forma_pgto' => $this->input->post('formaPgto'),
                'tipo' => $this->input->post('tipo')
            );

            if ($this->vendas_model->add('lancamentos',$data) == TRUE) {
                
                $venda = $this->input->post('vendas_id');

                $this->db->set('faturado',1);
                $this->db->set('valorTotal',$this->input->post('valor'));
                $this->db->where('idVendas', $venda);
                $this->db->update('vendas');

                $this->session->set_flashdata('success','Venda faturada com sucesso!');
                $json = array('result'=>  true);
                echo json_encode($json);
                die();
            } else {
                $this->session->set_flashdata('error','Ocorreu um erro ao tentar faturar venda.');
                $json = array('result'=>  false);
                echo json_encode($json);
                die();
            }
        }

        $this->session->set_flashdata('error','Ocorreu um erro ao tentar faturar venda.');
        $json = array('result'=>  false);
        echo json_encode($json);     
        
    }

    public function faturar_parc() {

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';
        $urlAtual = $this->input->post('urlAtual');
        if ($this->form_validation->run('receita_parc') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
			$qtdparcelas_parc = $this->input->post('qtdparcelas_parc');
			$entrada = $this->input->post('entrada');
			$valor_parc = $this->input->post('valor_parc');
			$valorparcelas = ($valor_parc - $entrada) / $qtdparcelas_parc;

			$dia_pgto = $this->input->post('dia_pgto');
			$dia_base_pgto = $this->input->post('dia_base_pgto');

            try {
                $dia_pgto = explode('/', $dia_pgto);
                $dia_pgto = $dia_pgto[2].'-'.$dia_pgto[1].'-'.$dia_pgto[0];
                
                $dia_base_pgto = explode('/', $dia_base_pgto);
                $dia_base_pgto = $dia_base_pgto[2].'-'.$dia_base_pgto[1].'-'.$dia_base_pgto[0];

            } catch (Exception $e) {
               $dia_pgto = date('Y/m/d');
               $dia_base_pgto = date('Y/m/d');
            }

		if($entrada == 0){
			$loops = 1;
			while ($loops <= $qtdparcelas_parc){

            $myDateTimeISO = $dia_base_pgto;
            $loopsmes = $loops - 1;
			$addThese = $loopsmes;
			$myDateTime = new DateTime($myDateTimeISO);
			$myDayOfMonth = date_format($myDateTime,'j');
			date_modify($myDateTime,"+$addThese months");

			//Find out if the day-of-month has dropped
			$myNewDayOfMonth = date_format($myDateTime,'j');
			if ($myDayOfMonth > 28 && $myNewDayOfMonth < 4){
			//If so, fix by going back the number of days that have spilled over
			    date_modify($myDateTime,"-$myNewDayOfMonth days");
			}

            $data = array(
                'descricao' => set_value('descricao_parc'),
                'valor' => $valorparcelas,
                'clientes_id' => $this->input->post('clientes_id_parc'),
                'data_vencimento' => date_format($myDateTime,"Y-m-d"),
                'baixado' => 0,
                'cliente_fornecedor' => set_value('cliente_parc'),
                'forma_pgto' => $this->input->post('formaPgto_parc'),
                'tipo' => $this->input->post('tipo_parc')
            );

            if ($this->vendas_model->add('lancamentos',$data) == TRUE) {
                
                $venda = $this->input->post('vendas_id_parc');

                $this->db->set('faturado',1);
                $this->db->set('valorTotal',$this->input->post('valor_parc'));
                $this->db->where('idVendas', $venda);
                $this->db->update('vendas');
			if($loops == $qtdparcelas_parc){
				$this->session->set_flashdata('success','Venda faturada com sucesso!');
                $json = array('result'=>  true);
                echo json_encode($json);
                die();
			}
            } else {
                $this->session->set_flashdata('error','Ocorreu um erro ao tentar faturar venda.');
                $json = array('result'=>  false);
                echo json_encode($json);
                die();
            }
			$loops++;
			}

		}else{
			$data1 = array(
                'descricao' => set_value('descricao_parc'),
                'valor' => $entrada,
                'clientes_id' => $this->input->post('clientes_id_parc'),
                'data_vencimento' => $dia_pgto,
                'baixado' => 1,
                'cliente_fornecedor' => set_value('cliente_parc'),
                'forma_pgto' => $this->input->post('formaPgto_parc'),
                'tipo' => $this->input->post('tipo_parc')
            );

            if ($this->vendas_model->add1('lancamentos',$data1) == TRUE) {

                $venda = $this->input->post('vendas_id_parc');
                $this->db->set('faturado',1);
                $this->db->set('valorTotal',$this->input->post('valor_parc'));
                $this->db->where('idVendas', $venda);
                $this->db->update('vendas');
            } else {
                $this->session->set_flashdata('error','Ocorreu um erro ao tentar faturar venda.');
                $json = array('result'=>  false);
                echo json_encode($json);
                die();
            }
	
			$loops = 1;
			while ($loops <= $qtdparcelas_parc){

            $myDateTimeISO = $dia_base_pgto;
            $loopsmes = $loops - 1;
			$addThese = $loopsmes;
			$myDateTime = new DateTime($myDateTimeISO);
			$myDayOfMonth = date_format($myDateTime,'j');
			date_modify($myDateTime,"+$addThese months");

			//Find out if the day-of-month has dropped
			$myNewDayOfMonth = date_format($myDateTime,'j');
			if ($myDayOfMonth > 28 && $myNewDayOfMonth < 4){
			//If so, fix by going back the number of days that have spilled over
			    date_modify($myDateTime,"-$myNewDayOfMonth days");
			}

            $data = array(
                'descricao' => set_value('descricao_parc'),
                'valor' => $valorparcelas,
                'clientes_id' => $this->input->post('clientes_id_parc'),
                'data_vencimento' => date_format($myDateTime,"Y-m-d"),
                'baixado' => 0,
                'cliente_fornecedor' => set_value('cliente_parc'),
                'forma_pgto' => $this->input->post('formaPgto_parc'),
                'tipo' => $this->input->post('tipo_parc')
            );

            if ($this->vendas_model->add('lancamentos',$data) == TRUE) {

                $venda = $this->input->post('vendas_id_parc');
                $this->db->set('faturado',1);
                $this->db->set('valorTotal',$this->input->post('valor_parc'));
                $this->db->where('idVendas', $venda);
                $this->db->update('vendas');
			if($loops == $qtdparcelas_parc){
				$this->session->set_flashdata('success','Venda faturada com sucesso!');
                $json = array('result'=>  true);
                echo json_encode($json);
                die();
			}
            } else {
                $this->session->set_flashdata('error','Ocorreu um erro ao tentar faturar venda.');
                $json = array('result'=>  false);
                echo json_encode($json);
                die();
            }
			$loops++;
			}	
		}
        }

        $this->session->set_flashdata('error','Ocorreu um erro ao tentar faturar venda.');
        $json = array('result'=>  false);
        echo json_encode($json);    
        
    }
}

