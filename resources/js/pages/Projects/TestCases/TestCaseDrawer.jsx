import AttachmentsPanel from '@/components/AttachmentsPanel';
import Discussion from '@/components/Discussion';
import Dropzone from '@/components/Dropzone';
import useForm from '@/hooks/useForm';
import { date, dateTime } from '@/utils/datetime';
import { issueCode, issueStatusColors } from '@/utils/issue';
import { storyOptions, testCaseCode } from '@/utils/testCase';
import { router, usePage } from '@inertiajs/react';
import {
  Anchor,
  Badge,
  Breadcrumbs,
  Button,
  Drawer,
  Flex,
  Group,
  Select,
  Stack,
  Tabs,
  Text,
  TextInput,
  Textarea,
  rem,
} from '@mantine/core';
import { DateTimePicker } from '@mantine/dates';
import { IconBug } from '@tabler/icons-react';
import dayjs from 'dayjs';
import classes from '../Tasks/Drawers/css/TaskDrawer.module.css';
import StepsEditor from './StepsEditor';

/**
 * Adds a new test case when `testCase` is null, otherwise edits it.
 * Mount it with a `key` per test case so the form starts from its values.
 */
export default function TestCaseDrawer({ opened, testCase, defaultStoryId = null, onClose }) {
  const { project, epics, statuses } = usePage().props;

  const isEditing = !!testCase;
  const readOnly = isEditing ? !can('edit test case') : !can('create test case');

  const [form, submit, updateValue] = useForm(
    isEditing ? 'put' : 'post',
    isEditing
      ? route('projects.test-cases.update', [project.id, testCase.id])
      : route('projects.test-cases.store', project.id),
    {
      title: testCase?.title ?? '',
      story_id: (testCase?.story_id ?? defaultStoryId)?.toString() ?? null,
      description: testCase?.description ?? '',
      preconditions: testCase?.preconditions ?? '',
      test_data: testCase?.test_data ?? '',
      environment: testCase?.environment ?? '',
      status: testCase?.status ?? 'not_run',
      executed_at: testCase?.executed_at ? dayjs(testCase.executed_at).toDate() : null,
      steps: (testCase?.steps ?? []).map(step => ({
        action: step.action,
        expected_result: step.expected_result ?? '',
      })),
      ...(isEditing ? {} : { attachments: [] }),
    }
  );

  const detailsForm = (
    <form
      onSubmit={event =>
        submit(event, { preserveScroll: true, forceFormData: !isEditing, onSuccess: onClose })
      }
      className={classes.inner}
    >
      <div className={classes.content}>
        <TextInput
          label='Title'
          placeholder='What is being tested'
          required
          data-autofocus
          value={form.data.title}
          onChange={event => updateValue('title', event.target.value)}
          error={form.errors.title}
          readOnly={readOnly}
        />

        <Textarea
          label='Description'
          mt='md'
          autosize
          minRows={2}
          value={form.data.description}
          onChange={event => updateValue('description', event.target.value)}
          readOnly={readOnly}
        />

        <Textarea
          label='Preconditions'
          placeholder='What must be true before starting'
          mt='md'
          autosize
          minRows={2}
          value={form.data.preconditions}
          onChange={event => updateValue('preconditions', event.target.value)}
          readOnly={readOnly}
        />

        <Text
          size='sm'
          fw={500}
          mt='xl'
          mb={6}
        >
          Steps
        </Text>
        <StepsEditor
          steps={form.data.steps}
          onChange={steps => updateValue('steps', steps)}
          errors={form.errors}
          readOnly={readOnly}
        />

        <Textarea
          label='Test data'
          placeholder='Accounts, inputs or files used in the test'
          mt='xl'
          autosize
          minRows={2}
          value={form.data.test_data}
          onChange={event => updateValue('test_data', event.target.value)}
          readOnly={readOnly}
        />

        {!isEditing && (
          <Dropzone
            mt='xl'
            selected={form.data.attachments}
            onChange={files => updateValue('attachments', files)}
            remove={index =>
              updateValue(
                'attachments',
                form.data.attachments.filter((_, fileIndex) => fileIndex !== index)
              )
            }
          />
        )}

        {!readOnly && (
          <Flex
            justify='space-between'
            mt='xl'
          >
            <Button
              variant='transparent'
              w={100}
              disabled={form.processing}
              onClick={onClose}
            >
              Cancel
            </Button>
            <Button
              type='submit'
              w={160}
              loading={form.processing}
            >
              {isEditing ? 'Save changes' : 'Add test case'}
            </Button>
          </Flex>
        )}
      </div>

      <div className={classes.sidebar}>
        <Select
          label='Status'
          required
          allowDeselect={false}
          data={statuses}
          value={form.data.status}
          onChange={value => updateValue('status', value)}
          error={form.errors.status}
          readOnly={readOnly}
        />

        {form.data.status !== 'not_run' && (
          <DateTimePicker
            label='Run on'
            placeholder='Set when you save'
            mt='md'
            clearable
            valueFormat='DD MMM YYYY HH:mm'
            value={form.data.executed_at}
            onChange={value => updateValue('executed_at', value)}
            error={form.errors.executed_at}
            readOnly={readOnly}
          />
        )}
        {testCase?.executed_by_user && form.data.status !== 'not_run' && (
          <Text
            size='xs'
            c='dimmed'
            mt={4}
          >
            Run by {testCase.executed_by_user.name}
          </Text>
        )}

        <Select
          label='Story'
          placeholder='Without story'
          mt='md'
          searchable
          clearable
          data={storyOptions(epics)}
          value={form.data.story_id}
          onChange={value => updateValue('story_id', value)}
          error={form.errors.story_id}
          readOnly={readOnly}
          nothingFoundMessage='Add an epic and a story first'
        />

        <TextInput
          label='Environment'
          placeholder='e.g. Chrome, staging'
          mt='md'
          value={form.data.environment}
          onChange={event => updateValue('environment', event.target.value)}
          error={form.errors.environment}
          readOnly={readOnly}
        />
      </div>
    </form>
  );

  const linkedIssues = (
    <Stack gap='sm'>
      {can('create issue') && (
        <Button
          variant='light'
          w='fit-content'
          leftSection={<IconBug size={16} />}
          onClick={() =>
            router.get(
              route('projects.issues', { project: project.id, report_for_case: testCase.id })
            )
          }
        >
          Report issue from this case
        </Button>
      )}
      {testCase?.issues?.length ? (
        testCase.issues.map(issue => (
          <Group
            key={issue.id}
            gap='sm'
          >
            <Badge
              variant='light'
              color={issueStatusColors[issue.status]}
              radius='sm'
            >
              {issue.status.replace('_', ' ')}
            </Badge>
            <Anchor
              size='sm'
              href={route('projects.issues.open', [issue.project_id, issue.id])}
            >
              {issueCode(issue)}: {issue.title}
            </Anchor>
          </Group>
        ))
      ) : (
        <Text
          size='sm'
          c='dimmed'
        >
          No issues linked to this test case
        </Text>
      )}
    </Stack>
  );

  return (
    <Drawer
      opened={opened}
      onClose={onClose}
      title={
        <Text
          fz={rem(28)}
          fw={600}
          ml={25}
          my='sm'
        >
          {isEditing ? `${testCaseCode(testCase)}: ${testCase.title}` : 'Add new test case'}
        </Text>
      }
      position='right'
      size={1100}
      overlayProps={{ backgroundOpacity: 0.55, blur: 3 }}
      transitionProps={{ transition: 'slide-left', duration: 400, timingFunction: 'ease' }}
    >
      {isEditing && (
        <Breadcrumbs
          c='dark.3'
          ml={24}
          mb='xs'
          separator='I'
          separatorMargin='sm'
          styles={{ separator: { opacity: 0.3 } }}
        >
          <Text size='xs'>{project.name}</Text>
          {testCase.story?.epic && <Text size='xs'>{testCase.story.epic.title}</Text>}
          {testCase.story && <Text size='xs'>{testCase.story.title}</Text>}
          <Text size='xs'>
            Created by {testCase.created_by_user?.name ?? 'unknown user'} on{' '}
            {date(testCase.created_at)}
          </Text>
          {testCase.executed_at && <Text size='xs'>Last run {dateTime(testCase.executed_at)}</Text>}
        </Breadcrumbs>
      )}

      {isEditing ? (
        <Tabs
          defaultValue='details'
          keepMounted={false}
          mx={24}
        >
          <Tabs.List mb='lg'>
            <Tabs.Tab value='details'>Details</Tabs.Tab>
            <Tabs.Tab value='files'>Files ({testCase.attachments?.length ?? 0})</Tabs.Tab>
            <Tabs.Tab value='discussion'>Discussion</Tabs.Tab>
            <Tabs.Tab value='issues'>Issues ({testCase.issues?.length ?? 0})</Tabs.Tab>
          </Tabs.List>

          <Tabs.Panel value='details'>{detailsForm}</Tabs.Panel>
          <Tabs.Panel value='files'>
            <AttachmentsPanel
              initialFiles={testCase.attachments}
              uploadUrl={route('projects.test-cases.attachments.store', [project.id, testCase.id])}
              deleteUrl={file =>
                route('projects.test-cases.attachments.destroy', [project.id, testCase.id, file.id])
              }
              canEdit={!readOnly}
            />
          </Tabs.Panel>
          <Tabs.Panel value='discussion'>
            <Discussion
              listUrl={route('projects.test-cases.comments', [project.id, testCase.id])}
              storeUrl={route('projects.test-cases.comments.store', [project.id, testCase.id])}
            />
          </Tabs.Panel>
          <Tabs.Panel value='issues'>{linkedIssues}</Tabs.Panel>
        </Tabs>
      ) : (
        detailsForm
      )}
    </Drawer>
  );
}
