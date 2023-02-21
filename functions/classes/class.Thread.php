<?php
/**
* Implements threading in PHP
*
* @package <none>
* @version 1.0.0 - stable
* @author Tudor Barbu <miau@motane.lu>
* @copyright MIT
*/
class PingThread {
    const FUNCTION_NOT_CALLABLE = 10;
    const COULD_NOT_FORK = 15;
    const IPC_SOCKET_FAILED = 20;

	/**
	* possible errors
	*
	* @var array
	*/
    private $errors = array(
        PingThread::FUNCTION_NOT_CALLABLE => 'You must specify a valid function name that can be called from the current scope.',
        PingThread::COULD_NOT_FORK => 'pcntl_fork() returned a status of -1. No new process was created',
        PingThread::IPC_SOCKET_FAILED => 'socket_create_pair() returned a status of -1. No new process was created',
    );

	/**
	* callback for the function that should
	* run as a separate thread
	*
	* @var callable
	*/
    protected $runnable;

	/**
	* holds the current process id
	*
	* @var integer
	*/
    private $pid;

	/**
	* holds the parent process id
	*
	* @var integer
	*/
    private $ppid;

	/**
	* holds the exit code after the child dies
	*/
    private $exitCode = -1;

    /**
	* holds type - needed for fping
	*/
	public $stype = "ping";

	/**
	 * holds sockets for fping IPC
	 *
	 * @var array
	 */
	private $sockets = [null, null];

	/**
	* checks if threading is supported by the current
	* PHP configuration
	*
	* @return boolean
	*/
    public static function available(&$errmsg = null) {
        $required_extensions = ['posix','pcntl'];
        $required_functions  = ['posix_getpid','posix_getppid','posix_mkfifo','posix_kill',
                                'pcntl_fork','pcntl_waitpid','pcntl_wifexited','pcntl_wexitstatus','pcntl_signal'];

        if ($errmsg = php_feature_missing($required_extensions, $required_functions))
            return false;

        return true;
    }

	/**
	* class constructor - you can pass
	* the callback function as an argument
	*
	* @param callback $_runnable
	*/
    public function __construct( $_runnable = null ) {
		if (!is_null($_runnable))
			$this->setRunnable($_runnable);

		/* On Windows we need to use AF_INET */
		$domain = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ?  STREAM_PF_INET : STREAM_PF_UNIX;
		$this->sockets = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

		if ($this->sockets === false)
			throw new Exception($this->getError(PingThread::IPC_SOCKET_FAILED), PingThread::IPC_SOCKET_FAILED);

		stream_set_blocking($this->sockets[0], 1);
		stream_set_blocking($this->sockets[1], 1);
	}

	/**
	 * Class destructor
	 */
	public function __destruct() {
		if (!is_null($this->sockets[0]))
			fclose($this->sockets[0]);

		if (!is_null($this->sockets[1]))
			fclose($this->sockets[1]);
	}

	/**
	 * Send FPing response to parent process
	 *
	 * @param mixed $results
	 * @return void
	 */
	private function ipc_send_data($results) {
		if (is_null($this->sockets[0]) || is_null($this->sockets[1]))
			return false;

		// Send results
		fclose($this->sockets[1]);
		fwrite($this->sockets[0], json_encode($results) . "\n");
		fclose($this->sockets[0]);

		$this->sockets[0] = null;
		$this->sockets[1] = null;

		return true;
	}

	/**
	 * Read FPing response from child process
	 *
	 * @return mixed
	 */
	public function ipc_recv_data() {
		if (is_null($this->sockets[0]) || is_null($this->sockets[1]))
			return null;

		// Read results
		fclose($this->sockets[0]);
		$response = fgets($this->sockets[1]);
		if (is_string($response) && !is_blank($response)) {
			$response = pf_json_decode($response);
		} else {
			$response = null;
		}
		fclose($this->sockets[1]);

		$this->sockets[0] = null;
		$this->sockets[1] = null;

		return $response;
	}

	/**
	* sets the callback
	*
	* @param callback $_runnable
	* @return callback
	*/
    public function setRunnable( $_runnable ) {
        if( self::runnableOk( $_runnable ) ) {
            $this->runnable = $_runnable;
        }
        else {
            throw new Exception( $this->getError( PingThread::FUNCTION_NOT_CALLABLE ), PingThread::FUNCTION_NOT_CALLABLE );
        }
    }

	/**
	* gets the callback
	*
	* @return callback
	*/
    public function getRunnable() {
        return $this->runnable;
    }

	/**
	* checks if the callback is ok (the function/method
	* actually exists and is runnable from the current
	* context)
	*
	* can be called statically
	*
	* @param string $_runnable
	* @return boolean
	*/
    public static function runnableOk( $_runnable ) {
        return ( function_exists( $_runnable ) && is_callable( $_runnable ) );
    }

	/**
	* returns the process id (pid) of the simulated thread
	*
	* @return int
	*/
    public function getPid() {
        return $this->pid;
    }

	/**
	* Blocks until the thread has exited
	*
	* @return mixed
	*/
    public function join($wait = true) {
		$pid = pcntl_waitpid($this->pid, $status, $wait ? 0 : WNOHANG);

		if ($pid === -1) {
			// Error
			return -1;
		} elseif ($pid === 0) {
			// child is still alive
			return 0;
		} else {
			$this->exitCode = pcntl_wexitstatus($status);
			return $pid;
		}
    }

	/**
	* return exit code of child (waits if still alive)
	*
	* @return int
	*/
    public function getExitCode() {
        $this->join();
        return $this->exitCode;
    }

	/**
	* starts the thread, all the parameters are
	* passed to the callback function
	*
	* @return void
	*/
    public function start() {
        $pid = @ pcntl_fork();
        if( $pid == -1 ) {
            throw new Exception( $this->getError( PingThread::COULD_NOT_FORK ), PingThread::COULD_NOT_FORK );
        }
        if( $pid ) {
            // parent
            $this->pid = $pid;
        }
        else {
            // child
            $this->pid = posix_getpid();//pid (child)
            $this->ppid = posix_getppid();//pid (parent)

            pcntl_signal( SIGTERM, array( $this, 'signalHandler' ) );
            $arguments = func_get_args();
            if ( !empty( $arguments ) ) {
                call_user_func_array( $this->runnable, $arguments );
            }
            else {
                call_user_func( $this->runnable );
            }

            //and kill the child using posix_kill ( exit(0) duplicates headers!! )
            posix_kill($this->pid , SIGKILL);
            exit( 0 );
        }
    }

	/**
	* starts the thread, all the parameters are
	* passed to the callback function
	*
	*	modification for fping threading for cron scanning
	*
	* @return void
	*/
    public function start_fping() {
		$results = null;
		$pid = pcntl_fork();

		if( $pid == -1 ) { //error forking, no child is created
			throw new Exception( $this->getError( PingThread::COULD_NOT_FORK ), PingThread::COULD_NOT_FORK );
		}else if ( $pid ) {// parent
			$this->pid = $pid;

		} else { // child
			$this->pid = posix_getpid();//pid (child)
			$this->ppid = posix_getppid();//pid (parent)

			pcntl_signal( SIGTERM, array( $this, 'signalHandler' ) );
			$array_args = func_get_args();
			if ( !empty( $array_args ) ) {
				$results = call_user_func_array( $this->runnable, $array_args );
			}else{
				$results = call_user_func( $this->runnable );
			}

			$this->ipc_send_data($results);

			//and kill the child using posix_kill ( exit(0) duplicates headers!! )
			posix_kill( $this->pid , SIGKILL);
			exit(0);
		}
    }

	/**
	* attempts to stop the thread
	* returns true on success and false otherwise
	*
	* @param integer $_signal - SIGKILL/SIGTERM
	* @param boolean $_wait
	*/
    public function stop( $_signal = SIGKILL, $_wait = false ) {
		if ($this->join(false) === 0) {
			posix_kill($this->pid, $_signal);
			if ($_wait) {
				$this->join();
			}
		}
	}

	/**
	* alias of stop();
	*
	* @return boolean
	*/
    public function kill( $_signal = SIGKILL, $_wait = false ) {
        return $this->stop( $_signal, $_wait );
    }

	/**
	* gets the error's message based on
	* its id
	*
	* @param integer $_code
	* @return string
	*/
    public function getError( $_code ) {
        if ( isset( $this->errors[$_code] ) ) {
            return $this->errors[$_code];
        }
        else {
            return 'No such error code ' . $_code . '! Quit inventing errors!!!';
        }
    }

	/**
	* signal handler
	*
	* @param integer $_signal
	*/
    protected function signalHandler( $_signal ) {
        switch( $_signal ) {
            case SIGTERM:
                exit( 0 );
            break;
        }
    }
}

// EOF
