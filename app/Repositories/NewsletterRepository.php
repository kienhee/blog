<?php

namespace App\Repositories;

use App\Models\Newsletter;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class NewsletterRepository extends BaseRepository
{
    public function __construct(Newsletter $model)
    {
        parent::__construct($model);
    }

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 0;

    public function getStatusLabel()
    {
        return [
            self::STATUS_ACTIVE => 'Đã đăng ký',
            self::STATUS_INACTIVE => 'Đã hủy',
        ];
    }

    public function gridData()
    {
        $query = Newsletter::query();
        $query->select([
            'id',
            'email',
            'status',
            'subscribed_at',
            'created_at',
            'scroll_percentage',
            'time_on_page',
            'is_human',
            'spam_score',
        ]);

        return $query;
    }

    public function filterData($grid)
    {
        $request = request();
        $email = $request->input('email');
        $status = $request->input('status');
        $isHuman = $request->input('is_human');

        if ($email) {
            $grid->where('email', 'like', '%'.$email.'%');
        }

        if ($status !== null && $status !== '') {
            $grid->where('status', $status);
        }

        if ($isHuman !== null && $isHuman !== '') {
            $grid->where('is_human', $isHuman);
        }

        // Sắp xếp theo thời gian đăng ký mới nhất
        $grid->orderBy('created_at', 'desc');

        return $grid;
    }

    public function renderDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('email', function ($row) {
                return htmlspecialchars($row->email);
            })
            ->addColumn('status', function ($row) {
                $labels = $this->getStatusLabel();
                $status = $row->status;
                $label = isset($labels[$status]) ? $labels[$status] : 'Không xác định';
                $class = [
                    self::STATUS_ACTIVE => 'bg-label-success',
                    self::STATUS_INACTIVE => 'bg-label-secondary',
                ];
                $badgeClass = isset($class[$status]) ? $class[$status] : 'bg-label-secondary';

                return '<span class="badge '.$badgeClass.'">'.$label.'</span>';
            })
            ->addColumn('is_human', function ($row) {
                if ($row->is_human === null) {
                    return '<span class="text-muted">-</span>';
                }
                
                if ($row->is_human) {
                    return '<span class="badge bg-label-success"><i class="bx bx-user-check me-1"></i>Người</span>';
                } else {
                    return '<span class="badge bg-label-danger"><i class="bx bx-bot me-1"></i>Bot/Spam</span>';
                }
            })
            ->addColumn('spam_score', function ($row) {
                if ($row->spam_score === null) {
                    return '<span class="text-muted">-</span>';
                }
                
                $score = (int) $row->spam_score;
                $badgeClass = 'bg-label-success';
                if ($score >= 70) {
                    $badgeClass = 'bg-label-danger';
                } elseif ($score >= 50) {
                    $badgeClass = 'bg-label-warning';
                } elseif ($score >= 30) {
                    $badgeClass = 'bg-label-info';
                }
                
                return '<span class="badge '.$badgeClass.'">'.$score.'</span>';
            })
            ->addColumn('behavior', function ($row) {
                $html = '<div class="small">';
                
                if ($row->scroll_percentage !== null) {
                    $html .= '<div class="mb-1"><i class="bx bx-down-arrow-alt me-1"></i>Scroll: <strong>'.number_format($row->scroll_percentage, 1).'%</strong></div>';
                }
                
                if ($row->time_on_page !== null) {
                    $html .= '<div><i class="bx bx-time me-1"></i>Thời gian: <strong>'.$row->time_on_page.'s</strong></div>';
                }
                
                if ($row->scroll_percentage === null && $row->time_on_page === null) {
                    $html = '<span class="text-muted">-</span>';
                } else {
                    $html .= '</div>';
                }
                
                return $html;
            })
            ->addColumn('subscribed_at', function ($row) {
                if ($row->subscribed_at) {
                    return '<span class="text-muted">'.$row->subscribed_at->format('d/m/Y H:i').'</span>';
                }

                return '<span class="text-muted">-</span>';
            })
            ->addColumn('created_at', function ($row) {
                return '<span class="text-muted">'.$row->created_at->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('action', function ($row) {
                $user = Auth::user();
                $canDelete = $user && $user->can('newsletter.delete');

                $html = '<div class="d-inline-block text-nowrap">';

                if ($canDelete) {
                    $html .= '<button type="button"
                            class="btn btn-sm btn-icon delete-item"
                            data-id="'.$row->id.'"
                            data-url="'.route('admin.newsletters.destroy', $row->id).'"
                            title="Xóa">
                        <i class="bx bx-trash"></i>
                    </button>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['status', 'subscribed_at', 'created_at', 'is_human', 'spam_score', 'behavior', 'action'])
            ->make(true);
    }

    /**
     * Tính toán spam score dựa trên hành vi người dùng
     *
     * @param float $scrollPercentage Phần trăm scroll (0-100)
     * @param int $timeOnPage Thời gian ở trên trang (giây)
     * @return int Spam score (0-100, càng cao càng spam)
     */
    public function calculateSpamScore($scrollPercentage, $timeOnPage)
    {
        $score = 0;

        // Kiểm tra scroll percentage
        if ($scrollPercentage < 30) {
            $score += 80; // Hầu như chắc chắn là bot
        } elseif ($scrollPercentage < 50) {
            $score += 60; // Rất có khả năng là bot
        } elseif ($scrollPercentage < 70) {
            $score += 40; // Bot thường không scroll xuống
        }

        // Kiểm tra thời gian ở trên trang
        if ($timeOnPage < 5) {
            $score += 50;
        } elseif ($timeOnPage < 10) {
            $score += 30;
        } elseif ($timeOnPage < 20) {
            $score += 15;
        }

        // Nếu cả scroll < 30% và time < 5 giây thì gần như chắc chắn là bot
        if ($scrollPercentage < 30 && $timeOnPage < 5) {
            $score = 100;
        }

        // Nếu scroll >= 70% và time >= 20 giây thì giảm score (hành vi bình thường)
        if ($scrollPercentage >= 70 && $timeOnPage >= 20) {
            $score = max(0, $score - 30);
        }

        return min(100, max(0, $score));
    }

    /**
     * Đăng ký newsletter với spam detection
     *
     * @param string $email
     * @param float $scrollPercentage
     * @param int $timeOnPage
     * @return array
     */
    public function subscribe($email, $scrollPercentage = 0, $timeOnPage = 0)
    {
        $spamScore = $this->calculateSpamScore($scrollPercentage, $timeOnPage);
        $isHuman = $spamScore < 50;
        $status = $isHuman ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;

        $existingNewsletter = $this->model->where('email', $email)->first();

        if ($existingNewsletter) {
            if ($existingNewsletter->status === self::STATUS_ACTIVE) {
                return [
                    'success' => false,
                    'message' => 'Email này đã được đăng ký trước đó.',
                ];
            }

            if ($isHuman) {
                $existingNewsletter->update([
                    'status' => self::STATUS_ACTIVE,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                    'scroll_percentage' => $scrollPercentage,
                    'time_on_page' => $timeOnPage,
                    'is_human' => $isHuman,
                    'spam_score' => $spamScore,
                ]);

                return [
                    'success' => true,
                    'message' => 'Cảm ơn bạn đã đăng ký lại nhận tin tức!',
                ];
            } else {
                $existingNewsletter->update([
                    'scroll_percentage' => $scrollPercentage,
                    'time_on_page' => $timeOnPage,
                    'is_human' => $isHuman,
                    'spam_score' => $spamScore,
                ]);

                return [
                    'success' => true,
                    'message' => 'Cảm ơn bạn đã đăng ký nhận tin tức!',
                ];
            }
        }

        $this->model->create([
            'email' => $email,
            'status' => $status,
            'subscribed_at' => $isHuman ? now() : null,
            'scroll_percentage' => $scrollPercentage,
            'time_on_page' => $timeOnPage,
            'is_human' => $isHuman,
            'spam_score' => $spamScore,
        ]);

        return [
            'success' => true,
            'message' => 'Cảm ơn bạn đã đăng ký nhận tin tức!',
        ];
    }
}

