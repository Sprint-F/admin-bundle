<?php

namespace SprintF\Bundle\Admin\Controller;

use SprintF\Bundle\Admin\Form\FormFilter;
use SprintF\Bundle\Admin\Handler\EntityHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractAdminController extends AbstractController
{
    use AdminControllerIndexTrait;
    use AdminControllerEditSaveDeleteTrait;
    use AdminControllerLogTrait;
    use AdminControllerActionTrait;

    abstract protected static function getEntityClass(): string;

    protected const ENTITITES_PER_PAGE = 25;

    protected EntityHandler $eh;

    #[Required]
    public function setEntityHandler(EntityHandler $entityHandler)
    {
        $this->eh = $entityHandler;
    }

    protected SluggerInterface $slugger;

    #[Required]
    public function setSlugger(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }
}
