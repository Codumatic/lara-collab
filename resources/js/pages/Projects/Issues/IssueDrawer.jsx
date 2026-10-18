import Dropzone from '@/components/Dropzone';
import RichTextEditor from '@/components/RichTextEditor';
import useForm from '@/hooks/useForm';
import { date } from '@/utils/datetime';
import { issueCode } from '@/utils/issue';
import { usePage } from '@inertiajs/react';
import {
  Anchor,
  Breadcrumbs,
  Button,
  Drawer,
  Flex,
  Select,
  Tabs,
  Text,
  TextInput,
  Textarea,
  rem,
} from '@mantine/core';
import LabelsDropdown from '../Tasks/Drawers/LabelsDropdown';
import classes from '../Tasks/Drawers/css/TaskDrawer.module.css';
import AttachmentsPanel from '@/components/AttachmentsPanel';
import Discussion from '@/components/Discussion';
import IssueHistory from './IssueHistory';

/**
 * Adds a new issue when `issue` is null, otherwise edits the given issue.
 * Mount it with a `key` per issue so the form starts from that issue's values.
 */
export default function IssueDrawer({
  opened,
  issue,
  defaultStatus = null,
  defaultTestCaseId = null,
  onClose,
}) {
  const { project, assignableUsers, labels, dropdowns, testCaseOptions } = usePage().props;

  const isEditing = !!issue;
  const readOnly = isEditing && (!can('edit issue') || !!issue.archived_at);

  const [form, submit, updateValue] = useForm(
    isEditing ? 'put' : 'post',
    isEditing
      ? route('projects.issues.update', [project.id, issue.id])
      : route('projects.issues.store', project.id),
    {
      title: issue?.title ?? '',
      description: issue?.description ?? '',
      steps_to_reproduce: issue?.steps_to_reproduce ?? '',
      expected_result: issue?.expected_result ?? '',
      actual_result: issue?.actual_result ?? '',
      status: issue?.status ?? defaultStatus ?? 'new',
      severity: issue?.severity ?? null,
      priority: issue?.priority ?? null,
      assigned_to_user_id: issue?.assigned_to_user_id?.toString() ?? null,
      labels: (issue?.labels ?? []).map(label => label.id),
      test_case_id: (issue?.test_case_id ?? defaultTestCaseId)?.toString() ?? null,
      ...(isEditing ? {} : { attachments: [] }),
    }
  );

  const userOptions = assignableUsers.map(user => ({
    value: user.id.toString(),
    label: user.name,
  }));

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
          placeholder='Short summary of the problem'
          required
          data-autofocus
          value={form.data.title}
          onChange={event => updateValue('title', event.target.value)}
          error={form.errors.title}
          readOnly={readOnly}
        />

        <Text
          size='sm'
          fw={500}
          mt='xl'
          mb={4}
        >
          Description
        </Text>
        <RichTextEditor
          placeholder='Describe the issue'
          content={form.data.description}
          height={180}
          onChange={content => updateValue('description', content)}
          readOnly={readOnly}
        />

        <Text
          size='sm'
          fw={500}
          mt='xl'
          mb={4}
        >
          Steps to reproduce
        </Text>
        <RichTextEditor
          placeholder='1. Go to … 2. Click on …'
          content={form.data.steps_to_reproduce}
          height={160}
          onChange={content => updateValue('steps_to_reproduce', content)}
          readOnly={readOnly}
        />

        <Textarea
          label='Expected result'
          placeholder='What should happen'
          mt='xl'
          autosize
          minRows={2}
          value={form.data.expected_result}
          onChange={event => updateValue('expected_result', event.target.value)}
          error={form.errors.expected_result}
          readOnly={readOnly}
        />

        <Textarea
          label='Actual result'
          placeholder='What actually happens'
          mt='md'
          autosize
          minRows={2}
          value={form.data.actual_result}
          onChange={event => updateValue('actual_result', event.target.value)}
          error={form.errors.actual_result}
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
              w={140}
              loading={form.processing}
            >
              {isEditing ? 'Save changes' : 'Add issue'}
            </Button>
          </Flex>
        )}
      </div>

      <div className={classes.sidebar}>
        <Select
          label='Status'
          required
          allowDeselect={false}
          data={dropdowns.statuses}
          value={form.data.status}
          onChange={value => updateValue('status', value)}
          error={form.errors.status}
          readOnly={readOnly}
        />

        <Select
          label='Severity'
          placeholder='Select severity'
          mt='md'
          clearable
          data={dropdowns.severities}
          value={form.data.severity}
          onChange={value => updateValue('severity', value)}
          error={form.errors.severity}
          readOnly={readOnly}
        />

        <Select
          label='Priority'
          placeholder='Select priority'
          mt='md'
          clearable
          data={dropdowns.priorities}
          value={form.data.priority}
          onChange={value => updateValue('priority', value)}
          error={form.errors.priority}
          readOnly={readOnly}
        />

        <Select
          label='Assignee'
          placeholder='Select assignee'
          mt='md'
          searchable
          clearable
          data={userOptions}
          value={form.data.assigned_to_user_id}
          onChange={value => updateValue('assigned_to_user_id', value)}
          error={form.errors.assigned_to_user_id}
          readOnly={readOnly}
        />

        <LabelsDropdown
          mt='md'
          items={labels}
          selected={form.data.labels}
          onChange={values => updateValue('labels', values)}
          disabled={readOnly}
        />

        <Select
          label='Link to case'
          placeholder='Select test case'
          mt='md'
          searchable
          clearable
          data={testCaseOptions}
          value={form.data.test_case_id}
          onChange={value => updateValue('test_case_id', value)}
          error={form.errors.test_case_id}
          readOnly={readOnly}
          nothingFoundMessage='No test cases in this project'
        />
        {form.data.test_case_id && can('view test cases') && (
          <Anchor
            size='xs'
            mt={4}
            display='block'
            href={route('projects.test-cases.open', [project.id, form.data.test_case_id])}
          >
            Open test case
          </Anchor>
        )}
      </div>
    </form>
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
          {isEditing ? `${issueCode(issue)}: ${issue.title}` : 'Add new issue'}
        </Text>
      }
      position='right'
      size={1000}
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
          <Text size='xs'>{issueCode(issue)}</Text>
          <Text size='xs'>
            Reported by {issue.reported_by_user?.name ?? 'unknown user'} on {date(issue.created_at)}
          </Text>
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
            <Tabs.Tab value='files'>Files ({issue.attachments?.length ?? 0})</Tabs.Tab>
            <Tabs.Tab value='discussion'>Discussion</Tabs.Tab>
            <Tabs.Tab value='history'>History</Tabs.Tab>
          </Tabs.List>

          <Tabs.Panel value='details'>{detailsForm}</Tabs.Panel>
          <Tabs.Panel value='files'>
            <AttachmentsPanel
              initialFiles={issue.attachments}
              uploadUrl={route('projects.issues.attachments.store', [project.id, issue.id])}
              deleteUrl={file =>
                route('projects.issues.attachments.destroy', [project.id, issue.id, file.id])
              }
              canEdit={!readOnly}
            />
          </Tabs.Panel>
          <Tabs.Panel value='discussion'>
            <Discussion
              listUrl={route('projects.issues.comments', [project.id, issue.id])}
              storeUrl={route('projects.issues.comments.store', [project.id, issue.id])}
            />
          </Tabs.Panel>
          <Tabs.Panel value='history'>
            <IssueHistory issue={issue} />
          </Tabs.Panel>
        </Tabs>
      ) : (
        detailsForm
      )}
    </Drawer>
  );
}
