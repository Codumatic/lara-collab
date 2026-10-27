import { openConfirmModal } from '@/components/ConfirmModal';
import { issueCode } from '@/utils/issue';
import { router } from '@inertiajs/react';
import { ActionIcon, Menu, rem } from '@mantine/core';
import { IconArchive, IconArchiveOff, IconDots } from '@tabler/icons-react';

export default function IssueActions({ issue }) {
  const isArchived = !!issue.archived_at;
  const canArchive = can('archive issue') && !isArchived;
  const canRestore = can('restore issue') && isArchived;

  if (!canArchive && !canRestore) {
    return null;
  }

  const openArchiveModal = () =>
    openConfirmModal({
      type: 'danger',
      title: 'Archive issue',
      content: `Are you sure you want to archive ${issueCode(issue)}?`,
      confirmLabel: 'Archive',
      confirmProps: { color: 'red' },
      onConfirm: () =>
        router.delete(route('projects.issues.destroy', [issue.project_id, issue.id]), {
          preserveScroll: true,
        }),
    });

  const openRestoreModal = () =>
    openConfirmModal({
      type: 'info',
      title: 'Restore issue',
      content: `Are you sure you want to restore ${issueCode(issue)}?`,
      confirmLabel: 'Restore',
      confirmProps: { color: 'blue' },
      onConfirm: () =>
        router.post(
          route('projects.issues.restore', [issue.project_id, issue.id]),
          {},
          { preserveScroll: true }
        ),
    });

  return (
    <Menu
      withArrow
      position='bottom-end'
      withinPortal
      shadow='md'
      transitionProps={{ duration: 100, transition: 'pop-top-right' }}
      offset={{ mainAxis: 3, alignmentAxis: 5 }}
    >
      <Menu.Target>
        <ActionIcon
          variant='subtle'
          color='gray'
          onClick={event => event.stopPropagation()}
        >
          <IconDots
            style={{ width: rem(16), height: rem(16) }}
            stroke={1.5}
          />
        </ActionIcon>
      </Menu.Target>
      <Menu.Dropdown onClick={event => event.stopPropagation()}>
        {canRestore && (
          <Menu.Item
            leftSection={
              <IconArchiveOff
                style={{ width: rem(16), height: rem(16) }}
                stroke={1.5}
              />
            }
            color='blue'
            onClick={openRestoreModal}
          >
            Restore
          </Menu.Item>
        )}
        {canArchive && (
          <Menu.Item
            leftSection={
              <IconArchive
                style={{ width: rem(16), height: rem(16) }}
                stroke={1.5}
              />
            }
            color='red'
            onClick={openArchiveModal}
          >
            Archive
          </Menu.Item>
        )}
      </Menu.Dropdown>
    </Menu>
  );
}
