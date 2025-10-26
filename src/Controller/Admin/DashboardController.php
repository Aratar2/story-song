<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use App\Entity\SongRequest;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin')]
class DashboardController extends AbstractDashboardController
{
    #[Route(path: '/', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Story Song')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Панель', 'fa fa-home', 'admin_dashboard');
        yield MenuItem::linkToCrud('Заявки', 'fa fa-music', SongRequest::class);
        yield MenuItem::linkToCrud('Админы', 'fa fa-user-shield', AdminUser::class);
        yield MenuItem::linkToRoute('Выйти', 'fa fa-sign-out', 'app_logout');
    }
}
