<?php
/**
 * Created by PhpStorm.
 * User: jokeikusunoki
 * Date: 15/9/25
 * Time: 下午4:08
 */
namespace DWD\DataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Cronjob
 * @package DWD\DataBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="cronjob")
 */
class CronJob
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
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $owner;

    /**
     * @var string
     * @ORM\Column(type="string", length=45, nullable=false)
     */
    private $mobile;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $job;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default"=true})
     */
    private $msg_switch_flag = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false})
     */
    private $msg_send_flag = false;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false, options={"default"=0})
     */
    private $last_execute_id;

    /**
     * @var bool
     * @ORM\Column(name="execute_cycle_enabled", type="boolean", nullable=false)
     */
    private $executeCycleEnabled;

    /**
     * @var integer
     * @ORM\Column(name="execute_cycle", type="integer", nullable=true)
     */
    private $executeCycle;

    /**
     * @var integer
     * @ORM\Column(name="execute_point_minute", type="integer", nullable=true)
     */
    private $executePointMinute;

    /**
     * @var integer
     * @ORM\Column(name="execute_point_hour", type="integer", nullable=true)
     */
    private $executePointHour;

    /**
     * @var integer
     * @ORM\Column(name="execute_point_day", type="integer", nullable=true)
     */
    private $executePointDay;

    /**
     * @var integer
     * @ORM\Column(name="execute_point_month", type="integer", nullable=true)
     */
    private $executePointMonth;

    /**
     * @var integer
     * @ORM\Column(name="execute_point_week", type="integer", nullable=true)
     */
    private $executePointWeek;

    /**
     * @var integer
     * @ORM\Column(name="max_execute_time", type="integer")
     */
    private $maxExecuteTime;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $note;

    /**
     * @ORM\OneToMany(targetEntity="CronJobData", mappedBy="cronjob")
     */
    protected $cronjob_datas;

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
     * @return CronJob
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
     * Set owner
     *
     * @param string $owner
     * @return CronJob
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     * @return CronJob
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set job
     *
     * @param string $job
     * @return CronJob
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
     * Set msg_switch_flag
     *
     * @param boolean $msgSwitchFlag
     * @return CronJob
     */
    public function setMsgSwitchFlag($msgSwitchFlag)
    {
        $this->msg_switch_flag = $msgSwitchFlag;

        return $this;
    }

    /**
     * Get msg_switch_flag
     *
     * @return boolean
     */
    public function getMsgSwitchFlag()
    {
        return $this->msg_switch_flag;
    }

    /**
     * Set msg_send_flag
     *
     * @param boolean $msgSendFlag
     * @return CronJob
     */
    public function setMsgSendFlag($msgSendFlag)
    {
        $this->msg_send_flag = $msgSendFlag;

        return $this;
    }

    /**
     * Get msg_send_flag
     *
     * @return boolean
     */
    public function getMsgSendFlag()
    {
        return $this->msg_send_flag;
    }

    /**
     * Set last_execute_id
     *
     * @param integer $lastExecuteId
     * @return CronJob
     */
    public function setLastExecuteId($lastExecuteId)
    {
        $this->last_execute_id = $lastExecuteId;

        return $this;
    }

    /**
     * Get last_execute_id
     *
     * @return integer
     */
    public function getLastExecuteId()
    {
        return $this->last_execute_id;
    }

    /**
     * Set executeCycleEnabled
     *
     * @param boolean $executeCycleEnabled
     * @return CronJob
     */
    public function setExecuteCycleEnabled($executeCycleEnabled)
    {
        $this->executeCycleEnabled = $executeCycleEnabled;

        return $this;
    }

    /**
     * Get executeCycleEnabled
     *
     * @return boolean
     */
    public function getExecuteCycleEnabled()
    {
        return $this->executeCycleEnabled;
    }

    /**
     * Set executeCycle
     *
     * @param integer $executeCycle
     * @return CronJob
     */
    public function setExecuteCycle($executeCycle)
    {
        $this->executeCycle = $executeCycle;

        return $this;
    }

    /**
     * Get executeCycle
     *
     * @return integer
     */
    public function getExecuteCycle()
    {
        return $this->executeCycle;
    }

    /**
     * Set executePointMinute
     *
     * @param integer $executePointMinute
     * @return CronJob
     */
    public function setExecutePointMinute($executePointMinute)
    {
        $this->executePointMinute = $executePointMinute;

        return $this;
    }

    /**
     * Get executePointMinute
     *
     * @return integer
     */
    public function getExecutePointMinute()
    {
        return $this->executePointMinute;
    }

    /**
     * Set executePointHour
     *
     * @param integer $executePointHour
     * @return CronJob
     */
    public function setExecutePointHour($executePointHour)
    {
        $this->executePointHour = $executePointHour;

        return $this;
    }

    /**
     * Get executePointHour
     *
     * @return integer
     */
    public function getExecutePointHour()
    {
        return $this->executePointHour;
    }

    /**
     * Set executePointDay
     *
     * @param integer $executePointDay
     * @return CronJob
     */
    public function setExecutePointDay($executePointDay)
    {
        $this->executePointDay = $executePointDay;

        return $this;
    }

    /**
     * Get executePointDay
     *
     * @return integer
     */
    public function getExecutePointDay()
    {
        return $this->executePointDay;
    }

    /**
     * Set executePointMonth
     *
     * @param integer $executePointMonth
     * @return CronJob
     */
    public function setExecutePointMonth($executePointMonth)
    {
        $this->executePointMonth = $executePointMonth;

        return $this;
    }

    /**
     * Get executePointMonth
     *
     * @return integer
     */
    public function getExecutePointMonth()
    {
        return $this->executePointMonth;
    }

    /**
     * Set executePointWeek
     *
     * @param integer $executePointWeek
     * @return CronJob
     */
    public function setExecutePointWeek($executePointWeek)
    {
        $this->executePointWeek = $executePointWeek;

        return $this;
    }

    /**
     * Get executePointWeek
     *
     * @return integer
     */
    public function getExecutePointWeek()
    {
        return $this->executePointWeek;
    }

    /**
     * Set maxExecuteTime
     *
     * @param integer $maxExecuteTime
     * @return CronJob
     */
    public function setMaxExecuteTime($maxExecuteTime)
    {
        $this->maxExecuteTime = $maxExecuteTime;

        return $this;
    }

    /**
     * Get maxExecuteTime
     *
     * @return integer
     */
    public function getMaxExecuteTime()
    {
        return $this->maxExecuteTime;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return CronJob
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }
}
