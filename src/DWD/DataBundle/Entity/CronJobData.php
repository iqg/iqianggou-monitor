<?php
/**
 * Created by PhpStorm.
 * User: jokeikusunoki
 * Date: 15/9/25
 * Time: 下午4:09
 */
namespace DWD\DataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CronJobData
 * @package DWD\DataBundle\Entity
 * @ORM\Entity(repositoryClass="CronJobDataRepository")
 * @ORM\Table(name="cronjob_data",
 *      indexes={
 *          @ORM\Index(name="code", columns={"code"}),
 *          @ORM\Index(name="name", columns={"name"}),
 *          @ORM\Index(name="flag", columns={"flag"}),
 *          @ORM\Index(name="start_time", columns={"start_time"})
 *      }
 * )
 */
class CronJobData
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $job;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true, nullable=true)
     */
    private $msg;

    /**
     * @var integer
     * @ORM\Column(name="start_time", type="integer", nullable=true)
     */
    private $startTime;

    /**
     * @var integer
     * @ORM\Column(name="stop_time", type="integer", nullable=true)
     */
    private $stopTime;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $flag;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cost;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $code;

    /**
     * @ORM\ManyToOne(targetEntity="CronJob", inversedBy="cronjob_datas")
     * @ORM\JoinColumn(name="name", referencedColumnName="name", nullable=false)
     */
    protected $cronjob;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CronJobData
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set job
     *
     * @param string $job
     * @return CronJobData
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Get job
     *
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set msg
     *
     * @param string $msg
     * @return CronJobData
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;

        return $this;
    }

    /**
     * Get msg
     *
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * Set startTime
     *
     * @param integer $startTime
     * @return CronJobData
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set stopTime
     *
     * @param integer $stopTime
     * @return CronJobData
     */
    public function setStopTime($stopTime)
    {
        $this->stopTime = $stopTime;

        return $this;
    }

    /**
     * Get stopTime
     *
     * @return integer
     */
    public function getStopTime()
    {
        return $this->stopTime;
    }

    /**
     * Set flag
     *
     * @param boolean $flag
     * @return CronJobData
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;

        return $this;
    }

    /**
     * Get flag
     *
     * @return boolean
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * Set cost
     *
     * @param integer $cost
     * @return CronJobData
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return integer
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set code
     *
     * @param integer $code
     * @return CronJobData
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }
}
