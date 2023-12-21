<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    // AccountSettings
    public function Account()
    {
        return view('admin.templates.AccountSettings.Account');
    }
    public function Notifications()
    {
        return view('admin.templates.AccountSettings.Notifications');
    }
    public function Connections()
    {
        return view('admin.templates.AccountSettings.Connections');
    }
    // AccountSettings
    public function Login()
    {
        $layout = 'auth';
        return view('admin.templates.Auth.Login', compact('layout'));
    }
    public function Register()
    {
        $layout = 'auth';
        return view('admin.templates.Auth.Register', compact('layout'));
    }
    public function ForgotPassword()
    {
        $layout = 'auth';
        return view('admin.templates.Auth.ForgotPassword', compact('layout'));
    }

    // Misc
    public function Error()
    {
        $layout = "misc";
        return view('admin.templates.Misc.Error', compact('layout'));
    }
    public function UnderMaintenance()
    {
        $layout = "misc";
        return view('admin.templates.Misc.UnderMaintenance', compact('layout'));
    }
    // Components
    public function Boxicons()
    {
        return view('admin.templates.Components.Boxicons');
    }
    public function Cards()
    {
        return view('admin.templates.Components.Cards');
    }

    public function Accordion()
    {
        return view('admin.templates.Components.Accordion');
    }
    public function Alerts()
    {
        return view('admin.templates.Components.Alerts');
    }
    public function Badges()
    {
        return view('admin.templates.Components.Badges');
    }
    public function Buttons()
    {
        return view('admin.templates.Components.Buttons');
    }
    public function Carousel()
    {
        return view('admin.templates.Components.Carousel');
    }
    public function Collapse()
    {
        return view('admin.templates.Components.Collapse');
    }
    public function Dropdowns()
    {
        return view('admin.templates.Components.Dropdowns');
    }
    public function Footer()
    {
        return view('admin.templates.Components.Footer');
    }
    public function ListGroups()
    {
        return view('admin.templates.Components.ListGroups');
    }
    public function Modals()
    {
        return view('admin.templates.Components.Modals');
    }
    public function Navbar()
    {
        return view('admin.templates.Components.Navbar');
    }
    public function Offcanvas()
    {
        return view('admin.templates.Components.Offcanvas');
    }
    public function PaginationBreadcrumbs()
    {
        return view('admin.templates.Components.PaginationBreadcrumbs');
    }
    public function Progress()
    {
        return view('admin.templates.Components.Progress');
    }
    public function Spinners()
    {
        return view('admin.templates.Components.Spinners');
    }
    public function TabsPills()
    {
        return view('admin.templates.Components.TabsPills');
    }
    public function Toasts()
    {
        return view('admin.templates.Components.Toasts');
    }
    public function TooltipsPopovers()
    {
        return view('admin.templates.Components.TooltipsPopovers');
    }
    public function Typography()
    {
        return view('admin.templates.Components.Typography');
    }
    // Extended UI 
    public function PerfectScrollbar()
    {
        return view('admin.templates.Extended-UI.PerfectScrollbar');
    }
    public function TextDivider()
    {
        return view('admin.templates.Extended-UI.TextDivider');
    }
    // FormElements
    public function BasicInputs()
    {
        return view('admin.templates.FormElements.BasicInputs');
    }
    public function InputGroups()
    {
        return view('admin.templates.FormElements.InputGroups');
    }
    // FormLayouts
    public function HorizontalForm()
    {
        return view('admin.templates.FormLayouts.HorizontalForm');
    }
    public function VerticalForm()
    {
        return view('admin.templates.FormLayouts.VerticalForm');
    }

    // Tables
    public function Tables()
    {
        return view('admin.templates.Tables.Tables');
    }
}