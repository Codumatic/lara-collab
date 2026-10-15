import ArchivedFilterButton from '@/components/ArchivedFilterButton';
import Pagination from '@/components/Pagination';
import Layout from '@/layouts/MainLayout';
import { reloadWithQuery, reloadWithoutQueryParams, replaceUrlWithoutReload } from '@/utils/route';
import { router, usePage } from '@inertiajs/react';
import { ActionIcon, Button, Group, Text, Title, Tooltip } from '@mantine/core';
import {
  IconArrowLeft,
  IconLayoutKanban,
  IconLayoutList,
  IconPlus,
} from '@tabler/icons-react';
import { useEffect, useState } from 'react';
import IssuesTable from '@/pages/Issues/IssuesTable';
import IssueActions from './IssueActions';
import IssueDrawer from './IssueDrawer';
import IssuesBoard from './IssuesBoard';

let currentProject = null;

const IssuesIndex = () => {
  const { project, items, boardIssues, openedIssue, dropdowns } = usePage().props;
  currentProject = project;

  const isBoardView = !!boardIssues;

  const switchView = view => {
    if (view === 'board') {
      reloadWithQuery({ view: 'board' }, true);
    } else {
      reloadWithoutQueryParams({ exclude: ['view', 'page'] });
    }
  };

  const [drawer, setDrawer] = useState({ opened: false, issue: null });

  useEffect(() => {
    if (openedIssue) {
      setDrawer({ opened: true, issue: openedIssue });
    }
  }, []);

  const viewQuery = isBoardView ? { view: 'board' } : {};

  const openIssue = issue => {
    replaceUrlWithoutReload(
      route('projects.issues.open', { project: project.id, issue: issue.id, ...viewQuery })
    );
    setDrawer({ opened: true, issue });
  };

  const openNewIssue = (defaultStatus = null) =>
    setDrawer({ opened: true, issue: null, defaultStatus });

  const closeDrawer = () => {
    if (drawer.issue) {
      replaceUrlWithoutReload(route('projects.issues', { project: project.id, ...viewQuery }));
    }
    setDrawer(state => ({ ...state, opened: false }));
  };

  return (
    <>
      <Group
        justify='space-between'
        align='flex-end'
        mb='xl'
      >
        <div>
          <Button
            variant='transparent'
            radius='xl'
            size='sm'
            color='gray'
            pl={0}
            leftSection={<IconArrowLeft size={14} />}
            onClick={() => router.get(route('projects.tasks', project.id))}
          >
            Back to tasks
          </Button>
          <Title
            order={1}
            mt={4}
          >
            {project.name}{' '}
            <Text
              size='1.5rem'
              fw={400}
              c='dimmed'
              span
            >
              Issues
            </Text>
          </Title>
        </div>

        <Group>
          {!route().params.archived && (
            <ActionIcon.Group>
              <Tooltip
                label='List view'
                openDelay={250}
                withArrow
              >
                <ActionIcon
                  size='lg'
                  variant={isBoardView ? 'default' : 'filled'}
                  onClick={() => switchView('list')}
                >
                  <IconLayoutList style={{ width: '45%', height: '45%' }} />
                </ActionIcon>
              </Tooltip>
              <Tooltip
                label='Board view'
                openDelay={250}
                withArrow
              >
                <ActionIcon
                  size='lg'
                  variant={isBoardView ? 'filled' : 'default'}
                  onClick={() => switchView('board')}
                >
                  <IconLayoutKanban style={{ width: '45%', height: '45%' }} />
                </ActionIcon>
              </Tooltip>
            </ActionIcon.Group>
          )}
          {(can('archive issue') || can('restore issue')) && <ArchivedFilterButton />}
          {can('create issue') && !route().params.archived && (
            <Button
              leftSection={<IconPlus size={14} />}
              radius='xl'
              onClick={() => openNewIssue()}
            >
              Add issue
            </Button>
          )}
        </Group>
      </Group>

      {isBoardView ? (
        <IssuesBoard
          issues={boardIssues}
          dropdowns={dropdowns}
          onCardClick={openIssue}
          onAddIssue={openNewIssue}
        />
      ) : (
        <IssuesTable
          issues={items.data}
          dropdowns={dropdowns}
          onRowClick={openIssue}
          renderActions={issue => <IssueActions issue={issue} />}
        />
      )}

      {!isBoardView && items.meta.last_page > 1 && (
        <Pagination
          current={items.meta.current_page}
          pages={items.meta.last_page}
        />
      )}

      <IssueDrawer
        key={drawer.issue?.id ?? `new-${drawer.defaultStatus ?? ''}`}
        opened={drawer.opened}
        issue={drawer.issue}
        defaultStatus={drawer.defaultStatus}
        onClose={closeDrawer}
      />
    </>
  );
};

IssuesIndex.layout = page => <Layout title={`${currentProject?.name} · Issues`}>{page}</Layout>;

export default IssuesIndex;
