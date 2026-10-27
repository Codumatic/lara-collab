import { openConfirmModal } from '@/components/ConfirmModal';
import Pagination from '@/components/Pagination';
import SearchInput from '@/components/SearchInput';
import TableRowEmpty from '@/components/TableRowEmpty';
import Layout from '@/layouts/MainLayout';
import { dateTime, diffForHumans } from '@/utils/datetime';
import {
  currentUrlParams,
  reloadWithQuery,
  reloadWithoutQueryParams,
  replaceUrlWithoutReload,
} from '@/utils/route';
import { testCaseCode, testCaseStatusColors } from '@/utils/testCase';
import { router, usePage } from '@inertiajs/react';
import {
  ActionIcon,
  Badge,
  Button,
  FileButton,
  Grid,
  Group,
  Menu,
  MultiSelect,
  Table,
  Text,
  Title,
  Tooltip,
  rem,
} from '@mantine/core';
import {
  IconArrowLeft,
  IconDots,
  IconDownload,
  IconFileSpreadsheet,
  IconPlus,
  IconTrash,
  IconUpload,
} from '@tabler/icons-react';
import { useEffect, useState } from 'react';
import TestCaseDrawer from './TestCaseDrawer';
import TestCaseTree from './TestCaseTree';

let currentProject = null;

const TestCasesIndex = () => {
  const { project, items, openedTestCase, statuses } = usePage().props;
  currentProject = project;

  const [drawer, setDrawer] = useState({ opened: false, testCase: null });
  const [importing, setImporting] = useState(false);
  const params = currentUrlParams();

  useEffect(() => {
    if (openedTestCase) {
      setDrawer({ opened: true, testCase: openedTestCase });
    }
  }, []);

  const openTestCase = testCase => {
    router.get(
      route('projects.test-cases.open', { project: project.id, testCase: testCase.id, ...params }),
      {},
      { preserveState: true, preserveScroll: true, only: ['openedTestCase'] }
    );
    setDrawer({ opened: true, testCase: null, loadingId: testCase.id });
  };

  const openNewTestCase = () =>
    setDrawer({
      opened: true,
      testCase: null,
      defaultStoryId: params.story && params.story !== 'none' ? params.story : null,
    });

  const closeDrawer = () => {
    if (drawer.testCase) {
      replaceUrlWithoutReload(route('projects.test-cases', { project: project.id, ...params }));
    }
    setDrawer(state => ({ ...state, opened: false, loadingId: null }));
  };

  useEffect(() => {
    if (drawer.loadingId && openedTestCase?.id === drawer.loadingId) {
      setDrawer({ opened: true, testCase: openedTestCase });
    }
  }, [openedTestCase, drawer.loadingId]);

  const importFile = file => {
    if (!file) {
      return;
    }

    setImporting(true);
    router.post(
      route('projects.test-cases.import', project.id),
      { file },
      { forceFormData: true, preserveScroll: true, onFinish: () => setImporting(false) }
    );
  };

  const confirmDelete = testCase =>
    openConfirmModal({
      type: 'danger',
      title: 'Delete test case',
      content: `Delete ${testCaseCode(testCase)}? Issues linked to it stay, without the link.`,
      confirmLabel: 'Delete',
      confirmProps: { color: 'red' },
      onConfirm: () =>
        router.delete(route('projects.test-cases.destroy', [project.id, testCase.id]), {
          preserveScroll: true,
        }),
    });

  const selectedStatuses = (params.statuses ?? []).map(String);

  const rows = items.data.length ? (
    items.data.map(testCase => (
      <Table.Tr
        key={testCase.id}
        style={{ cursor: 'pointer' }}
        onClick={() => openTestCase(testCase)}
      >
        <Table.Td w={80}>
          <Text
            size='sm'
            fw={600}
            c='dimmed'
          >
            {testCaseCode(testCase)}
          </Text>
        </Table.Td>
        <Table.Td>
          <Text
            size='sm'
            fw={500}
            lineClamp={1}
          >
            {testCase.title}
          </Text>
          {testCase.story && (
            <Text
              size='xs'
              c='dimmed'
            >
              {testCase.story.epic?.title} › {testCase.story.title}
            </Text>
          )}
        </Table.Td>
        <Table.Td>
          <Badge
            variant='light'
            radius='sm'
            color={testCaseStatusColors[testCase.status]}
          >
            {statuses.find(status => status.value === testCase.status)?.label}
          </Badge>
        </Table.Td>
        <Table.Td>
          <Text size='sm'>{testCase.steps_count}</Text>
        </Table.Td>
        <Table.Td>
          {testCase.issues_count > 0 ? (
            <Badge
              variant='outline'
              color='red'
              radius='sm'
            >
              {testCase.issues_count}
            </Badge>
          ) : (
            <Text
              size='sm'
              c='dimmed'
            >
              —
            </Text>
          )}
        </Table.Td>
        <Table.Td>
          {testCase.executed_at ? (
            <Tooltip
              label={`${dateTime(testCase.executed_at)}${testCase.executed_by_user ? ` by ${testCase.executed_by_user.name}` : ''}`}
              openDelay={300}
              withArrow
            >
              <Text
                size='sm'
                c='dimmed'
              >
                {diffForHumans(testCase.executed_at)}
              </Text>
            </Tooltip>
          ) : (
            <Text
              size='sm'
              c='dimmed'
            >
              Never
            </Text>
          )}
        </Table.Td>
        <Table.Td w={50}>
          {can('delete test case') && (
            <Menu
              withinPortal
              position='bottom-end'
              shadow='md'
            >
              <Menu.Target>
                <ActionIcon
                  variant='subtle'
                  color='gray'
                  onClick={event => event.stopPropagation()}
                >
                  <IconDots style={{ width: rem(16), height: rem(16) }} />
                </ActionIcon>
              </Menu.Target>
              <Menu.Dropdown onClick={event => event.stopPropagation()}>
                <Menu.Item
                  color='red'
                  leftSection={<IconTrash style={{ width: rem(16), height: rem(16) }} />}
                  onClick={() => confirmDelete(testCase)}
                >
                  Delete
                </Menu.Item>
              </Menu.Dropdown>
            </Menu>
          )}
        </Table.Td>
      </Table.Tr>
    ))
  ) : (
    <TableRowEmpty colSpan={7} />
  );

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
              Test cases
            </Text>
          </Title>
        </div>

        <Group>
          <Button
            variant='default'
            radius='xl'
            leftSection={<IconDownload size={14} />}
            component='a'
            href={route('projects.test-cases.export', { project: project.id, ...params })}
          >
            Export
          </Button>
          {can('create test case') && (
            <Menu
              withinPortal
              position='bottom-end'
              shadow='md'
            >
              <Menu.Target>
                <Button
                  variant='default'
                  radius='xl'
                  loading={importing}
                  leftSection={<IconUpload size={14} />}
                >
                  Import
                </Button>
              </Menu.Target>
              <Menu.Dropdown>
                <FileButton
                  onChange={importFile}
                  accept='.xlsx,.xls,.csv'
                >
                  {props => (
                    <Menu.Item
                      {...props}
                      leftSection={<IconUpload size={14} />}
                    >
                      Upload Excel file
                    </Menu.Item>
                  )}
                </FileButton>
                <Menu.Item
                  component='a'
                  href={route('projects.test-cases.template', project.id)}
                  leftSection={<IconFileSpreadsheet size={14} />}
                >
                  Download empty template
                </Menu.Item>
              </Menu.Dropdown>
            </Menu>
          )}
          {can('create test case') && (
            <Button
              leftSection={<IconPlus size={14} />}
              radius='xl'
              onClick={openNewTestCase}
            >
              Add test case
            </Button>
          )}
        </Group>
      </Group>

      <Grid gutter='xl'>
        <Grid.Col span={{ base: 12, md: 3 }}>
          <TestCaseTree />
        </Grid.Col>

        <Grid.Col span={{ base: 12, md: 9 }}>
          <Group
            gap='xs'
            mb='md'
          >
            <SearchInput
              placeholder='Search title or TC-number'
              search={search => reloadWithQuery({ search, page: undefined }, true)}
              w={260}
            />
            <MultiSelect
              placeholder={selectedStatuses.length ? undefined : 'Status'}
              aria-label='Status'
              data={statuses}
              value={selectedStatuses}
              onChange={values =>
                values.length
                  ? reloadWithQuery({ statuses: values, page: undefined }, true)
                  : reloadWithoutQueryParams({ exclude: ['statuses', 'page'] })
              }
              clearable
              radius='xl'
              miw={140}
            />
          </Group>

          <Table.ScrollContainer minWidth={700}>
            <Table
              verticalSpacing='sm'
              highlightOnHover
            >
              <Table.Thead>
                <Table.Tr>
                  <Table.Th>ID</Table.Th>
                  <Table.Th>Title</Table.Th>
                  <Table.Th>Status</Table.Th>
                  <Table.Th>Steps</Table.Th>
                  <Table.Th>Issues</Table.Th>
                  <Table.Th>Last run</Table.Th>
                  <Table.Th />
                </Table.Tr>
              </Table.Thead>
              <Table.Tbody>{rows}</Table.Tbody>
            </Table>
          </Table.ScrollContainer>

          {items.meta.last_page > 1 && (
            <Pagination
              current={items.meta.current_page}
              pages={items.meta.last_page}
            />
          )}
        </Grid.Col>
      </Grid>

      {(drawer.testCase || !drawer.loadingId) && (
        <TestCaseDrawer
          key={drawer.testCase?.id ?? `new-${drawer.defaultStoryId ?? ''}`}
          opened={drawer.opened}
          testCase={drawer.testCase}
          defaultStoryId={drawer.defaultStoryId}
          onClose={closeDrawer}
        />
      )}
    </>
  );
};

TestCasesIndex.layout = page => (
  <Layout title={`${currentProject?.name} · Test cases`}>{page}</Layout>
);

export default TestCasesIndex;
