import Pagination from '@/components/Pagination';
import Layout from '@/layouts/MainLayout';
import IssueFilters from '@/pages/Issues/IssueFilters';
import IssuesTable from '@/pages/Issues/IssuesTable';
import { router, usePage } from '@inertiajs/react';
import { Breadcrumbs, Title } from '@mantine/core';

const MyWorkIssuesIndex = () => {
  const { items, dropdowns, filterOptions } = usePage().props;

  return (
    <>
      <Breadcrumbs
        fz={14}
        mb={30}
      >
        <div>My Work</div>
        <div>Issues</div>
      </Breadcrumbs>

      <Title
        order={1}
        mb={20}
      >
        Issues assigned to you
      </Title>

      <IssueFilters
        dropdowns={dropdowns}
        reporters={filterOptions.reporters}
        labels={filterOptions.labels}
        showAssigneeFilter={false}
      />

      <IssuesTable
        issues={items.data}
        dropdowns={dropdowns}
        showProject
        onRowClick={issue =>
          router.get(route('projects.issues.open', [issue.project_id, issue.id]))
        }
      />

      {items.meta.last_page > 1 && (
        <Pagination
          current={items.meta.current_page}
          pages={items.meta.last_page}
        />
      )}
    </>
  );
};

MyWorkIssuesIndex.layout = page => <Layout title='My Work · Issues'>{page}</Layout>;

export default MyWorkIssuesIndex;
