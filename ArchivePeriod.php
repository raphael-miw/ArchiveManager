<?php

/**
 * Created by PhpStorm.
 * User: raph
 * Date: 20/04/16
 * Time: 04:31
 */
class ArchivePeriod
{
    const MODE_KEEP_FIRST = "MODE_KEEP_FIRST";
    const MODE_KEEP_ALL = "MODE_KEEP_ALL";

    public $start_timestamp;
    public $end_timestamp;

    /** @var  PeriodCollection */
    protected $period_collection;

    /** @var  string */
    protected $mode;

    /** @var  ArchiveItem[] */
    protected $items = array();

    /** @var  DateTime */
    protected $end_date;

    /** @var  DateTime */
    private $start_date;

    /**
     * ArchivePeriod constructor.
     * @param DateTime $start_date
     * @param DateTime $end_date
     * @param null $end_time
     * @param string $start_time
     */
    public function __construct(DateTime $start_date,DateTime $end_date=null)
    {
        $this->start_date = $start_date;

        $this -> start_timestamp = $this->start_date -> getTimestamp();

        echo "<br>period : du ".$this->start_date->format('d/m/Y');

        if(!is_null($end_date)) {
            $this -> setEndDate($end_date);
        }
    }




    public function segmentBy(DateInterval $interval)
    {
        $this -> period_collection = new PeriodCollection();
        $period_start = $this->start_date;
        $end_timestamp = $this->getEndTimestamp();
        while($period_start->getTimestamp() < $end_timestamp) {
            $period_end = clone($period_start);
            $period_end -> add($interval);
            if($period_end->getTimestamp()>$end_timestamp){
                $period_end->setTimestamp($end_timestamp);
            }
            $this->period_collection -> addPeriod(new ArchivePeriod($period_start,$period_end));
            $period_start = $period_end;
        }
        return $this->period_collection;
    }

    /**
     * @param mixed $end_timestamp
     * @return ArchivePeriod
     */
    public function setEndTimestamp($end_timestamp)
    {
        $this->end_timestamp = $end_timestamp;
        return $this;
    }
    private function setEndDate(DateTime $end_date)
    {
        $this->end_date = $end_date;
        $this->setEndTimestamp($end_date->getTimestamp());
//        echo "<br>period : du ".$this->start_date->format('d/m/Y')." au ".$this->end_date->format("d/m/Y");

    }

    /**
     * @return int
     */
    public function getStartTimestamp()
    {
        return $this->start_timestamp;
    }

    public function keepFirst()
    {
        $this -> mode = self::MODE_KEEP_FIRST;
    }
    public function keepAll()
    {
        $this -> mode = self::MODE_KEEP_ALL;
    }

    public function hasDate($item_time)
    {
        return ($item_time>$this->start_timestamp && $item_time<=$this->end_timestamp);
    }

    public function addItem(ArchiveItem $item)
    {
        if(!is_null($this->period_collection)) {
            $this->period_collection->addItem($item);
        } else {
            $this ->items[]=$item;
            echo "<br> add ".$item->getTimestamp()." dans ";
        }

    }

    /**
     * @return mixed
     */
    public function getEndTimestamp()
    {
        if(is_null($this->end_timestamp)) return time();
        return $this->end_timestamp;
    }

    public function cleanArchives()
    {
        if(!is_null($this->period_collection)) {
            $this->period_collection->cleanArchives();
        } else {
            if($this->mode == self::MODE_KEEP_FIRST) {
                /** @var ArchiveItem $first_item */
                $first_item = null;
                foreach($this->items as $item) {
                    if(is_null($first_item)) {
                        $first_item = $item;
                    } elseif($item->getTimestamp()<$first_item->getTimestamp()) {
                        $first_item -> delete();
                        $first_item = $item;
                    }
                }
                if(!is_null($first_item)) {
                    echo "<br>(keep first) " . $first_item->getDate()->format('"d/m/Y') . "?";
                }
            } elseif($this->mode == self::MODE_KEEP_ALL) {
                foreach($this->items as $item) {
                    echo "<br>(keep all) " . $item->getDate()->format('"d/m/Y') . "?";
                }
            }
        }
    }


}