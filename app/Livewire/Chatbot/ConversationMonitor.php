<?php

declare(strict_types=1);

namespace App\Livewire\Chatbot;

use App\Enums\ChatbotChannel;
use App\Enums\ChatbotIntent;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChatbotConversation;
use App\Models\Tenant\ChatbotMessage;
use App\Services\AI\ChatbotService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ConversationMonitor extends Component
{
    use WithPagination;

    public Branch $branch;

    public ?string $selectedConversationId = null;

    #[Url]
    public string $channelFilter = '';

    #[Url]
    public string $search = '';

    public function mount(Branch $branch): void
    {
        $this->authorize('view', $branch);
        $this->branch = $branch;
    }

    /**
     * Select a conversation to view details.
     */
    public function selectConversation(string $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
    }

    /**
     * Clear selected conversation.
     */
    public function clearSelection(): void
    {
        $this->selectedConversationId = null;
    }

    /**
     * Reset all filters.
     */
    public function resetFilters(): void
    {
        $this->channelFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    #[Computed]
    public function conversations(): LengthAwarePaginator
    {
        $query = ChatbotConversation::query()
            ->where('branch_id', $this->branch->id)
            ->with('member')
            ->orderByDesc('last_message_at');

        if ($this->channelFilter !== '') {
            $channel = ChatbotChannel::tryFrom($this->channelFilter);
            if ($channel) {
                $query->where('channel', $channel);
            }
        }

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('phone_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('member', function ($mq): void {
                        $mq->where('first_name', 'like', '%'.$this->search.'%')
                            ->orWhere('last_name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        return $query->paginate(20);
    }

    #[Computed]
    public function selectedConversation(): ?ChatbotConversation
    {
        if (! $this->selectedConversationId) {
            return null;
        }

        return ChatbotConversation::with(['member', 'messages' => function ($q): void {
            $q->orderBy('created_at', 'asc');
        }])->find($this->selectedConversationId);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        $conversations = ChatbotConversation::where('branch_id', $this->branch->id)->get();
        $messages = ChatbotMessage::whereIn(
            'conversation_id',
            $conversations->pluck('id')
        )->get();

        // Intent distribution
        $intentCounts = $messages->where('direction', 'inbound')
            ->groupBy('intent')
            ->map->count();

        return [
            'total_conversations' => $conversations->count(),
            'active_today' => $conversations->where('last_message_at', '>=', now()->startOfDay())->count(),
            'total_messages' => $messages->count(),
            'inbound_messages' => $messages->where('direction', 'inbound')->count(),
            'sms_conversations' => $conversations->where('channel', ChatbotChannel::Sms)->count(),
            'whatsapp_conversations' => $conversations->where('channel', ChatbotChannel::WhatsApp)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function intentDistribution(): array
    {
        $messages = ChatbotMessage::query()
            ->whereHas('conversation', function ($q): void {
                $q->where('branch_id', $this->branch->id);
            })
            ->where('direction', 'inbound')
            ->whereNotNull('intent')
            ->get();

        $distribution = [];
        foreach (ChatbotIntent::cases() as $intent) {
            $distribution[$intent->value] = $messages->filter(
                fn ($m) => $m->intent === $intent->value
            )->count();
        }

        return $distribution;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function availableChannels(): array
    {
        return collect(ChatbotChannel::cases())
            ->mapWithKeys(fn (ChatbotChannel $channel) => [$channel->value => $channel->label()])
            ->all();
    }

    #[Computed]
    public function featureEnabled(): bool
    {
        return app(ChatbotService::class)->isEnabled();
    }

    public function render(): View
    {
        return view('livewire.chatbot.conversation-monitor');
    }
}
