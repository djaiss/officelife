<style lang="scss" scoped>
.answer-item:last-child {
  margin-bottom: 0;
}
</style>

<template>
  <div class="mb4 relative">
    <span class="db fw5 mb2">
      <span class="mr1">
        🎓
      </span> {{ $t('employee.question_title') }}
    </span>

    <div class="br3 bg-white box z-1">
      <ul v-if="questions.questions.length > 0" class="list pa3 ma0">
        <li v-for="question in questions.questions" :key="question.id" class="answer-item mb3" :data-cy="'question-title-' + question.id">
          <inertia-link :href="question.url" class="fw5 f5 lh-copy mb2">{{ question.title }}</inertia-link> <span class="gray f7">({{ question.number_of_answers }})</span>
        </li>
      </ul>
      <div class="ph3 pv2 tc f6 bt bb-gray">
        <inertia-link :href="questions.all_questions_url">{{ $t('company.questions_view_all', { count: questions.total_number_of_questions }) }}</inertia-link>
      </div>

      <!-- blank state -->
      <p v-if="questions.length == 0" class="mb0 mt0 lh-copy f6" data-cy="question-blank-state">{{ $t('employee.question_blank') }}</p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    questions: {
      type: Object,
      default: null,
    },
  },
};
</script>
