import RichTextEditor from '@/components/RichTextEditor';
import { dateTime, diffForHumans } from '@/utils/datetime';
import {
  Avatar,
  Box,
  Button,
  Center,
  Flex,
  Group,
  Loader,
  Stack,
  Text,
  Tooltip,
} from '@mantine/core';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import classes from '@/pages/Projects/Tasks/Drawers/css/Comments.module.css';

/**
 * Comment thread loaded from `listUrl` and posted to `storeUrl` (used by issues and test cases).
 */
export default function Discussion({ listUrl, storeUrl }) {
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [comment, setComment] = useState('');
  const editorRef = useRef(null);

  useEffect(() => {
    axios
      .get(listUrl)
      .then(({ data }) => setComments(data))
      .catch(() => alert('Failed to load comments'))
      .finally(() => setLoading(false));
  }, [listUrl]);

  const saveComment = () => {
    setSaving(true);

    axios
      .post(storeUrl, {
        content: comment,
      })
      .then(({ data }) => {
        setComments(current => [data.comment, ...current]);
        setComment('');
        editorRef.current?.setContent('');
      })
      .catch(() => alert('Failed to save comment'))
      .finally(() => setSaving(false));
  };

  return (
    <Box>
      <RichTextEditor
        ref={editorRef}
        placeholder='Write a comment'
        height={100}
        content={comment}
        onChange={content => setComment(content)}
      />
      <Flex justify='flex-end'>
        <Button
          mt='md'
          loading={saving}
          disabled={comment.length <= 7}
          onClick={saveComment}
        >
          Add comment
        </Button>
      </Flex>

      {loading ? (
        <Center mih={100}>
          <Loader />
        </Center>
      ) : comments.length === 0 ? (
        <Text
          c='dimmed'
          size='sm'
          ta='center'
          mt='xl'
        >
          No comments yet
        </Text>
      ) : (
        <Stack
          gap={30}
          mt='md'
        >
          {comments.map(item => (
            <div key={item.id}>
              <Group justify='space-between'>
                <Group>
                  <Avatar
                    src={item.user?.avatar}
                    radius='xl'
                    color='blue'
                  />
                  <div>
                    <Text
                      size='sm'
                      c='blue'
                      fw={500}
                    >
                      {item.user?.name ?? 'Deleted user'}
                    </Text>
                    <Text
                      size='xs'
                      c='dimmed'
                    >
                      {item.user?.job_title}
                    </Text>
                  </div>
                </Group>
                <Tooltip
                  label={dateTime(item.created_at)}
                  openDelay={250}
                  withArrow
                >
                  <Text
                    size='xs'
                    c='dimmed'
                  >
                    {diffForHumans(item.created_at)}
                  </Text>
                </Tooltip>
              </Group>
              <Text
                pl={54}
                pt={6}
                size='sm'
                className={classes.comment}
                dangerouslySetInnerHTML={{ __html: item.content }}
              />
            </div>
          ))}
        </Stack>
      )}
    </Box>
  );
}
