import { openConfirmModal } from '@/components/ConfirmModal';
import { reloadWithQuery, reloadWithoutQueryParams } from '@/utils/route';
import { router, usePage } from '@inertiajs/react';
import { ActionIcon, Button, Group, Menu, NavLink, Paper, Text, rem } from '@mantine/core';
import {
  IconDots,
  IconFolder,
  IconListCheck,
  IconPencil,
  IconPlus,
  IconQuestionMark,
  IconTrash,
} from '@tabler/icons-react';
import { useState } from 'react';
import TitleModal from './TitleModal';

const iconStyle = { width: rem(14), height: rem(14) };

/**
 * Project → Epic → Story navigation. The selected group lives in the URL (?epic= or ?story=).
 */
export default function TestCaseTree() {
  const { project, epics, counts } = usePage().props;
  const [modal, setModal] = useState(null);
  const params = route().params;
  const canManage = can('edit test case');

  const select = group => {
    if (group) {
      reloadWithQuery({ ...group, page: undefined }, false);
    } else {
      reloadWithoutQueryParams({ exclude: ['epic', 'story', 'page'] });
    }
  };

  const confirmDelete = (title, content, url) =>
    openConfirmModal({
      type: 'danger',
      title,
      content,
      confirmLabel: 'Delete',
      confirmProps: { color: 'red' },
      onConfirm: () => router.delete(url, { preserveScroll: true }),
    });

  const groupMenu = items => (
    <Menu
      withinPortal
      position='bottom-end'
      shadow='md'
    >
      <Menu.Target>
        <ActionIcon
          variant='subtle'
          color='gray'
          size='sm'
          onClick={event => event.stopPropagation()}
        >
          <IconDots style={iconStyle} />
        </ActionIcon>
      </Menu.Target>
      <Menu.Dropdown onClick={event => event.stopPropagation()}>
        {items.map(item => (
          <Menu.Item
            key={item.label}
            color={item.color}
            leftSection={item.icon}
            onClick={item.onClick}
          >
            {item.label}
          </Menu.Item>
        ))}
      </Menu.Dropdown>
    </Menu>
  );

  return (
    <Paper
      withBorder
      radius='md'
      p='xs'
    >
      <NavLink
        label='All test cases'
        leftSection={<IconListCheck style={iconStyle} />}
        rightSection={<Text size='xs'>{counts.all}</Text>}
        active={!params.epic && !params.story}
        onClick={() => select(null)}
      />

      {epics.map(epic => (
        <NavLink
          key={epic.id}
          label={
            <Group
              justify='space-between'
              wrap='nowrap'
              gap={4}
            >
              <Text
                size='sm'
                fw={600}
                truncate
              >
                {epic.title}
              </Text>
              {canManage &&
                groupMenu([
                  {
                    label: 'Add story',
                    icon: <IconPlus style={iconStyle} />,
                    onClick: () =>
                      setModal({
                        heading: `New story in "${epic.title}"`,
                        method: 'post',
                        url: route('projects.stories.store', [project.id, epic.id]),
                      }),
                  },
                  {
                    label: 'Rename epic',
                    icon: <IconPencil style={iconStyle} />,
                    onClick: () =>
                      setModal({
                        heading: 'Edit epic',
                        method: 'put',
                        url: route('projects.epics.update', [project.id, epic.id]),
                        title: epic.title,
                        description: epic.description,
                      }),
                  },
                  {
                    label: 'Delete epic',
                    color: 'red',
                    icon: <IconTrash style={iconStyle} />,
                    onClick: () =>
                      confirmDelete(
                        'Delete epic',
                        `Delete "${epic.title}" and its stories? Their test cases are kept under "Without story".`,
                        route('projects.epics.destroy', [project.id, epic.id])
                      ),
                  },
                ])}
            </Group>
          }
          leftSection={<IconFolder style={iconStyle} />}
          active={String(params.epic) === String(epic.id) && !params.story}
          defaultOpened
          onClick={() => select({ epic: epic.id })}
        >
          {epic.stories.map(story => (
            <NavLink
              key={story.id}
              label={
                <Group
                  justify='space-between'
                  wrap='nowrap'
                  gap={4}
                >
                  <Text
                    size='sm'
                    truncate
                  >
                    {story.title}
                  </Text>
                  <Group
                    gap={2}
                    wrap='nowrap'
                  >
                    <Text size='xs'>{story.test_cases_count}</Text>
                    {canManage &&
                      groupMenu([
                        {
                          label: 'Rename story',
                          icon: <IconPencil style={iconStyle} />,
                          onClick: () =>
                            setModal({
                              heading: 'Edit story',
                              method: 'put',
                              url: route('projects.stories.update', [
                                project.id,
                                epic.id,
                                story.id,
                              ]),
                              title: story.title,
                              description: story.description,
                            }),
                        },
                        {
                          label: 'Delete story',
                          color: 'red',
                          icon: <IconTrash style={iconStyle} />,
                          onClick: () =>
                            confirmDelete(
                              'Delete story',
                              `Delete "${story.title}"? Its test cases are kept under "Without story".`,
                              route('projects.stories.destroy', [project.id, epic.id, story.id])
                            ),
                        },
                      ])}
                  </Group>
                </Group>
              }
              active={String(params.story) === String(story.id)}
              onClick={() => select({ story: story.id })}
            />
          ))}
        </NavLink>
      ))}

      <NavLink
        label='Without story'
        leftSection={<IconQuestionMark style={iconStyle} />}
        rightSection={<Text size='xs'>{counts.withoutStory}</Text>}
        active={params.story === 'none'}
        onClick={() => select({ story: 'none' })}
      />

      {canManage && (
        <Button
          variant='subtle'
          size='xs'
          mt='xs'
          fullWidth
          leftSection={<IconPlus size={14} />}
          onClick={() =>
            setModal({
              heading: 'New epic',
              method: 'post',
              url: route('projects.epics.store', project.id),
            })
          }
        >
          Add epic
        </Button>
      )}

      {modal && (
        <TitleModal
          key={modal.url}
          request={modal}
          onClose={() => setModal(null)}
        />
      )}
    </Paper>
  );
}
