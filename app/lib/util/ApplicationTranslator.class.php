<?php
use Adianti\Core\AdiantiCoreTranslator;

/**
 * ApplicationTranslator
 *
 * @version    7.6
 * @package    util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class ApplicationTranslator
{
    private static $instance; // singleton instance
    private $lang;            // target language
    private $messages;
    private $sourceMessages;
    
    /**
     * Class Constructor
     */
    private function __construct()
    {
        $this->messages = [];
        $this->messages['en'] = [];
        $this->messages['pt'] = [];
        $this->messages['es'] = [];
        
        $this->messages['en'][] = 'University';
        $this->messages['pt'][] = 'Universidade';
        $this->messages['es'][] = 'Universidad';
        
        $this->messages['en'][] = 'City';
        $this->messages['pt'][] = 'Cidade';
        $this->messages['es'][] = 'Ciudad';
        
        $this->messages['en'][] = 'Select';
        $this->messages['pt'][] = 'Selecionar';
        $this->messages['es'][] = 'Seleccionar';
        
        $this->messages['en'][] = 'Select unit';
        $this->messages['pt'][] = 'Selecionar unidade';
        $this->messages['es'][] = 'Seleccionar unidad';

        $this->messages['en'][] = 'Courses';
        $this->messages['pt'][] = 'Cursos';
        $this->messages['es'][] = 'Cursos';

        $this->messages['en'][] = '📚 Courses';
        $this->messages['pt'][] = '📚 Cursos';
        $this->messages['es'][] = '📚 Cursos';

        $this->messages['en'][] = '📝 Page managements';
        $this->messages['pt'][] = '📝 Gestão de páginas';
        $this->messages['es'][] = '📝 Gestión de páginas';

        $this->messages['en'][] = '🔎 Search pages';
        $this->messages['pt'][] = '🔎 Buscar páginas';
        $this->messages['es'][] = '🔎 Buscar páginas';

        $this->messages['en'][] = 'Course';
        $this->messages['pt'][] = 'Curso';
        $this->messages['es'][] = 'Curso';

        $this->messages['en'][] = 'Add course link';
        $this->messages['pt'][] = 'Adicionar link do curso';
        $this->messages['es'][] = 'Agregar enlace del curso';

        $this->messages['en'][] = '📆 Histórico';
        $this->messages['pt'][] = '📆 Histórico';
        $this->messages['es'][] = '📆 Historia';

        $this->messages['en'][] = 'Usuário não encontrado.';
        $this->messages['pt'][] = 'Usuário não encontrado.';
        $this->messages['es'][] = 'Usuario no encontrado.';

        $this->messages['en'][] = 'Alterar configurações';
        $this->messages['pt'][] = 'Alterar configurações';
        $this->messages['es'][] = 'Cambiar configuración';

        $this->messages['en'][] = 'Iniciar robô';
        $this->messages['pt'][] = 'Iniciar robô';
        $this->messages['es'][] = 'iniciar robot';

        $this->messages['en'][] = 'Parar robô';
        $this->messages['pt'][] = 'Parar robô';
        $this->messages['es'][] = 'detener robot';

        $this->messages['en'][] = 'segundos';
        $this->messages['pt'][] = 'segundos';
        $this->messages['es'][] = 'artículos de segunda clase';

        $this->messages['en'][] = 'minuto';
        $this->messages['pt'][] = 'minuto';
        $this->messages['es'][] = 'minuto';

        $this->messages['en'][] = 'minutos';
        $this->messages['pt'][] = 'minutos';
        $this->messages['es'][] = 'minutos';

        $this->messages['en'][] = 'Valor';
        $this->messages['pt'][] = 'Valor';
        $this->messages['es'][] = 'Valor';

        $this->messages['en'][] = 'Quantidade';
        $this->messages['pt'][] = 'Quantidade';
        $this->messages['es'][] = 'Cantidad';

        $this->messages['en'][] = 'Desabilitado';
        $this->messages['pt'][] = 'Desabilitado';
        $this->messages['es'][] = 'Desactivado';

        $this->messages['en'][] = 'Habilitado';
        $this->messages['pt'][] = 'Habilitado';
        $this->messages['es'][] = 'Activado';

        $this->messages['en'][] = 'Valor operação:';
        $this->messages['pt'][] = 'Valor operação:';
        $this->messages['es'][] = 'Valor de operación:';

        $this->messages['en'][] = 'Proteções:';
        $this->messages['pt'][] = 'Proteções:';
        $this->messages['es'][] = 'Proteccinoes:';

        $this->messages['en'][] = 'Tempo expiração';
        $this->messages['pt'][] = 'Tempo expiração';
        $this->messages['es'][] = 'tiempo de vencimiento';

        $this->messages['en'][] = 'Classificação';
        $this->messages['pt'][] = 'Classificação';
        $this->messages['es'][] = 'Clasificación';

        $this->messages['en'][] = 'Fator multiplicador';
        $this->messages['pt'][] = 'Fator multiplicador';
        $this->messages['es'][] = 'factor multiplicador';

        $this->messages['en'][] = 'Stop WIN';
        $this->messages['pt'][] = 'Stop WIN';
        $this->messages['es'][] = 'Dejar de ganar';

        $this->messages['en'][] = 'Stop LOSS';
        $this->messages['pt'][] = 'Stop LOSS';
        $this->messages['es'][] = 'Detener PÉRDIDA';

        $this->messages['en'][] = 'Ciclo';
        $this->messages['pt'][] = 'Ciclo';
        $this->messages['es'][] = 'Ciclo';

        $this->messages['en'][] = 'Ativo';
        $this->messages['pt'][] = 'Ativo';
        $this->messages['es'][] = 'Activo';

        $this->messages['en'][] = 'Histórico de Ativos';
        $this->messages['pt'][] = 'Histórico de Ativos';
        $this->messages['es'][] = 'Historial de activos';

        $this->messages['en'][] = 'Gráfico do Ativo';
        $this->messages['pt'][] = 'Gráfico do Ativo';
        $this->messages['es'][] = 'Gráfico de activos';

        $this->messages['en'][] = 'Aguardando ativo...';
        $this->messages['pt'][] = 'Aguardando ativo...';
        $this->messages['es'][] = 'Esperando activo...';

        $this->messages['en'][] = 'Lucro/Perda';
        $this->messages['pt'][] = 'Lucro/Perda';
        $this->messages['es'][] = 'Ganancia/Pérdida';

        $this->messages['en'][] = 'Saldo Atual';
        $this->messages['pt'][] = 'Saldo Atual';
        $this->messages['es'][] = 'Saldo actual';

        $this->messages['en'][] = 'Maior Entrada';
        $this->messages['pt'][] = 'Maior Entrada';
        $this->messages['es'][] = 'Entrada más grande';

        $this->messages['en'][] = 'Assertividade';
        $this->messages['pt'][] = 'Assertividade';
        $this->messages['es'][] = 'Asertividad';

        $this->messages['en'][] = 'Tem certeza que deseja salvar a configuração?';
        $this->messages['pt'][] = 'Tem certeza que deseja salvar a configuração?';
        $this->messages['es'][] = '¿Está seguro de que desea guardar la configuración?';

        $this->messages['en'][] = 'Tem certeza que deseja parar a execução?';
        $this->messages['pt'][] = 'Tem certeza que deseja parar a execução?';
        $this->messages['es'][] = '¿Está seguro de que desea detener la ejecución?';

        $this->messages['en'][] = 'Você já possui esta estratégia na sua lista';
        $this->messages['pt'][] = 'Você já possui esta estratégia na sua lista';
        $this->messages['es'][] = 'Ya tienes esta estrategia en tu lista';

        $this->messages['en'][] = 'Estratégia copiada com sucesso.';
        $this->messages['pt'][] = 'Estratégia copiada com sucesso.';
        $this->messages['es'][] = 'Estrategia copiada con éxito.';

        $this->messages['en'][] = 'Robô iniciado no Dashboard';
        $this->messages['pt'][] = 'Robô iniciado no Dashboard';
        $this->messages['es'][] = 'Robot lanzado desde el panel';

        $this->messages['en'][] = 'Robô iniciado com sucesso.';
        $this->messages['pt'][] = 'Robô iniciado com sucesso.';
        $this->messages['es'][] = 'El robot se inició correctamente.';

        $this->messages['en'][] = 'Erro ao iniciar o robô.';
        $this->messages['pt'][] = 'Erro ao iniciar o robô.';
        $this->messages['es'][] = 'Error al iniciar el robot.';

        $this->messages['en'][] = 'Robô parado com sucesso.';
        $this->messages['pt'][] = 'Robô parado com sucesso.';
        $this->messages['es'][] = 'El robot se detuvo con éxito.';

        $this->messages['en'][] = 'Gráfico do Ativo:';
        $this->messages['pt'][] = 'Gráfico do Ativo:';
        $this->messages['es'][] = 'Gráfico de activos:';

        $this->messages['en'][] = 'Entrada às';
        $this->messages['pt'][] = 'Entrada às';
        $this->messages['es'][] = 'Entrada en';

        $this->messages['en'][] = 'Robô em execução';
        $this->messages['pt'][] = 'Robô em execução';
        $this->messages['es'][] = 'Robot corriendo';

        $this->messages['en'][] = 'Robô parado';
        $this->messages['pt'][] = 'Robô parado';
        $this->messages['es'][] = 'Robot detenido';

        $this->messages['en'][] = 'Operação';
        $this->messages['pt'][] = 'Operação';
        $this->messages['es'][] = 'Operación';

        $this->messages['en'][] = 'Entrada';
        $this->messages['pt'][] = 'Entrada';
        $this->messages['es'][] = 'Entrada';

        $this->messages['en'][] = 'Todos';
        $this->messages['pt'][] = 'Todos';
        $this->messages['es'][] = 'Todo';

        $this->messages['en'][] = 'Criptomoeda';
        $this->messages['pt'][] = 'Criptomoeda';
        $this->messages['es'][] = 'Criptomoneda';

        $this->messages['en'][] = 'Forex';
        $this->messages['pt'][] = 'Forex';
        $this->messages['es'][] = 'Forex';

        $this->messages['en'][] = 'OTC';
        $this->messages['pt'][] = 'OTC';
        $this->messages['es'][] = 'OTC';

        $this->messages['en'][] = 'Treinamento';
        $this->messages['pt'][] = 'Treinamento';
        $this->messages['es'][] = 'Capacitación';

        $this->messages['en'][] = 'Data exp.';
        $this->messages['pt'][] = 'Data exp.';
        $this->messages['es'][] = 'Exp. fecha';

        $this->messages['en'][] = 'Tempo exp.';
        $this->messages['pt'][] = 'Tempo exp.';
        $this->messages['es'][] = 'Tiempo';

        $this->messages['en'][] = 'Classific.';
        $this->messages['pt'][] = 'Classific.';
        $this->messages['es'][] = 'Clasific.';

        $this->messages['en'][] = 'Fator multip.';
        $this->messages['pt'][] = 'Fator multip.';
        $this->messages['es'][] = 'Factor multi.';

        $this->messages['en'][] = 'Stop WIN e Stop LOSS';
        $this->messages['pt'][] = 'Stop WIN e Stop LOSS';
        $this->messages['es'][] = 'Stop WIN y Stop LOSS';

        $this->messages['en'][] = 'Entrada Automática';
        $this->messages['pt'][] = 'Entrada Automática';
        $this->messages['es'][] = 'Entrada automática';

        $this->messages['en'][] = 'Ocorre após';
        $this->messages['pt'][] = 'Ocorre após';
        $this->messages['es'][] = 'Ocurre después';

        $this->messages['en'][] = 'Ciclo Stop LOSS';
        $this->messages['pt'][] = 'Ciclo Stop LOSS';
        $this->messages['es'][] = 'Ciclo Stop LOSS';

        $this->messages['en'][] = 'Tipo de espera';
        $this->messages['pt'][] = 'Tipo de espera';
        $this->messages['es'][] = 'Tipo de espera';

        $this->messages['en'][] = 'Qtde. de espera';
        $this->messages['pt'][] = 'Qtde. de espera';
        $this->messages['es'][] = 'Cant. espera';

        $this->messages['en'][] = 'Configuração';
        $this->messages['pt'][] = 'Configuração';
        $this->messages['es'][] = 'Ajustes';

        $this->messages['en'][] = 'Valor operação';
        $this->messages['pt'][] = 'Valor operação';
        $this->messages['es'][] = 'Valor oper.';

        $this->messages['en'][] = 'Proteções';
        $this->messages['pt'][] = 'Proteções';
        $this->messages['es'][] = 'Protecc.';

        $this->messages['en'][] = 'Modo';
        $this->messages['pt'][] = 'Modo';
        $this->messages['es'][] = 'Modo';
        
        $this->messages['en'][] = 'Entrada Auto.';
        $this->messages['pt'][] = 'Entrada Auto.';
        $this->messages['es'][] = 'Entrada Auto.';

        $this->messages['en'][] = 'Robô parado no Dashboard';
        $this->messages['pt'][] = 'Robô parado no Dashboard';
        $this->messages['es'][] = 'Robot detenido en el tablero';
        
        foreach ($this->messages as $lang => $messages)
        {
            $this->sourceMessages[$lang] = array_flip( $this->messages[ $lang ] );
        }
    }
    
    /**
     * Returns the singleton instance
     * @return  Instance of self
     */
    public static function getInstance()
    {
        // if there's no instance
        if (empty(self::$instance))
        {
            // creates a new object
            self::$instance = new self;
        }
        // returns the created instance
        return self::$instance;
    }
    
    /**
     * Define the target language
     * @param $lang Target language index
     */
    public static function setLanguage($lang, $global = true)
    {
        $instance = self::getInstance();
        
        if (in_array($lang, array_keys($instance->messages)))
        {
            $instance->lang = $lang;
        }
        
        if ($global)
        {
            AdiantiCoreTranslator::setLanguage( $lang );
            AdiantiTemplateTranslator::setLanguage( $lang );
        }
    }
    
    /**
     * Returns the target language
     * @return Target language index
     */
    public static function getLanguage()
    {
        $instance = self::getInstance();
        return $instance->lang;
    }
    
    /**
     * Translate a word to the target language
     * @param $word     Word to be translated
     * @return          Translated word
     */
    public static function translate($word, $source_language, $param1 = NULL, $param2 = NULL, $param3 = NULL, $param4 = NULL)
    {
        // get the self unique instance
        $instance = self::getInstance();
        // search by the numeric index of the word
        
        if (isset($instance->sourceMessages[$source_language][$word]) and !is_null($instance->sourceMessages[$source_language][$word]))
        {
            $key = $instance->sourceMessages[$source_language][$word];
            
            // get the target language
            $language = self::getLanguage();
            
            // returns the translated word
            $message = $instance->messages[$language][$key];
            
            if (isset($param1))
            {
                $message = str_replace('^1', $param1, $message);
            }
            if (isset($param2))
            {
                $message = str_replace('^2', $param2, $message);
            }
            if (isset($param3))
            {
                $message = str_replace('^3', $param3, $message);
            }
            if (isset($param4))
            {
                $message = str_replace('^4', $param4, $message);
            }
            return $message;
        }
        else
        {
            $word_template = AdiantiTemplateTranslator::translate($word, $source_language, $param1, $param2, $param3, $param4);
            
            if ($word_template)
            {
                return $word_template;
            }
            
            return 'Message not found: '. $word;
        }
    }
    
    /**
     * Translate a template file
     */
    public static function translateTemplate($template)
    {
        // search by translated words
        if(preg_match_all( '!_t\{(.*?)\}!i', $template, $match ) > 0)
        {
            foreach($match[1] as $word)
            {
                $translated = _t($word);
                $template = str_replace('_t{'.$word.'}', $translated, $template);
            }
        }
        
        if(preg_match_all( '!_tf\{(.*?), (.*?)\}!i', $template, $matches ) > 0)
        {
            foreach($matches[0] as $key => $match)
            {
                $raw        = $matches[0][$key];
                $word       = $matches[1][$key];
                $from       = $matches[2][$key];
                $translated = _tf($word, $from);
                $template = str_replace($raw, $translated, $template);
            }
        }
        return $template;
    }
}

/**
 * Facade to translate words from english
 * @param $word  Word to be translated
 * @param $param1 optional ^1
 * @param $param2 optional ^2
 * @param $param3 optional ^3
 * @return Translated word
 */
function _t($msg, $param1 = null, $param2 = null, $param3 = null)
{
    return ApplicationTranslator::translate($msg, 'en', $param1, $param2, $param3);
}

/**
 * Facade to translate words from specified language
 * @param $word  Word to be translated
 * @param $source_language  Source language
 * @param $param1 optional ^1
 * @param $param2 optional ^2
 * @param $param3 optional ^3
 * @return Translated word
 */
function _tf($msg, $source_language = 'en', $param1 = null, $param2 = null, $param3 = null)
{
    return ApplicationTranslator::translate($msg, $source_language, $param1, $param2, $param3);
}
