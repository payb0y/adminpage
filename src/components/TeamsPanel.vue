<template>
  <div class="teams-panel">
    <div class="teams-panel__toolbar">
      <span class="teams-panel__count">{{ teamCountLabel }}</span>
      <button v-if="canManage" type="button" class="iz-btn iz-btn--primary iz-btn--sm" @click="openCreate">
        + New team
      </button>
    </div>

    <div v-if="loading" class="iz-empty teams-panel__state">Loading teams...</div>
    <div v-else-if="error && !editorOpen" class="iz-error teams-panel__state" role="alert">
      <span>{{ error }}</span>
      <button type="button" class="iz-btn iz-btn--accent iz-btn--sm" @click="load">Try again</button>
    </div>
    <div v-else-if="!teams.length" class="iz-empty teams-panel__state">
      No teams yet. Create one to organize project capacity.
    </div>
    <ul v-else class="teams-panel__list">
      <li v-for="team in teams" :key="team.id" class="iz-row iz-row--card teams-panel__team">
        <div class="teams-panel__identity">
          <span class="iz-identity__avatar teams-panel__avatar">{{ initials(team.name) }}</span>
          <span class="iz-identity__body">
            <strong class="iz-identity__name">{{ team.name }}</strong>
            <small class="iz-identity__meta">{{ team.description || "No description" }}</small>
          </span>
        </div>
        <div class="teams-panel__facts">
          <span>
            <strong>{{ team.memberCount }}</strong>
            {{ team.memberCount === 1 ? "member" : "members" }}
          </span>
          <span><strong>{{ formatNumber(team.projectCapacity) }}</strong> project capacity</span>
          <span>{{ formatNumber(team.fte) }} FTE x {{ formatNumber(team.projectsPerFte) }}</span>
        </div>
        <div v-if="canManage" class="teams-panel__actions">
          <button type="button" class="iz-btn iz-btn--sm" @click="openEdit(team)">Edit</button>
          <button type="button" class="iz-btn iz-btn--danger iz-btn--sm" @click="openDelete(team)">Delete</button>
        </div>
      </li>
    </ul>

    <section class="iz-panel iz-panel--list teams-panel__assignments" aria-labelledby="team-assignments-title">
      <header class="teams-panel__assignments-header">
        <div>
          <h4 id="team-assignments-title" class="teams-panel__assignments-title">Responsible team by project</h4>
          <p>Assign one team to each project. Changes save automatically.</p>
        </div>
        <span class="iz-pill" :class="{ 'iz-pill--warning': unassignedCount > 0 }">
          {{ unassignedCount }} unassigned
        </span>
      </header>
      <div v-if="assignmentsLoading" class="iz-empty teams-panel__state">Loading project assignments...</div>
      <div v-else-if="assignmentsError" class="iz-error teams-panel__state" role="alert">
        <span>{{ assignmentsError }}</span>
        <button type="button" class="iz-btn iz-btn--accent iz-btn--sm" @click="loadAssignments">Try again</button>
      </div>
      <div v-else-if="!assignments.length" class="iz-empty teams-panel__state">No projects to assign yet.</div>
      <ul v-else class="teams-panel__assignment-list">
        <li v-for="project in assignments" :key="project.projectId" class="teams-panel__assignment-row">
          <span class="teams-panel__project">
            <strong>{{ project.projectName }}</strong>
            <small v-if="assignmentErrors[project.projectId]" class="teams-panel__assignment-error" role="alert">
              {{ assignmentErrors[project.projectId] }}
            </small>
          </span>
          <select
            class="iz-select teams-panel__assignment-select"
            :value="assignmentValue(project)"
            :disabled="!canManage || assignmentBusy[project.projectId] || !teams.length"
            :aria-label="'Responsible team for ' + project.projectName"
            @change="saveAssignment(project, $event)"
          >
            <option value="">Unassigned</option>
            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
          </select>
          <span class="teams-panel__assignment-status" aria-live="polite">
            {{ assignmentBusy[project.projectId] ? "Saving..." : "" }}
          </span>
        </li>
      </ul>
    </section>

    <div v-if="editorOpen" class="iz-modal-backdrop" @click.self="closeEditor">
      <div class="iz-modal teams-panel__modal" role="dialog" aria-modal="true" :aria-labelledby="'team-editor-title-' + _uid">
        <header class="iz-modal__header">
          <h3 :id="'team-editor-title-' + _uid">{{ editing ? "Edit team" : "New team" }}</h3>
          <button type="button" class="iz-close iz-close--sm" aria-label="Close" :disabled="saving" @click="closeEditor">&times;</button>
        </header>
        <form @submit.prevent="saveTeam">
          <div class="iz-modal__body teams-panel__form">
            <label class="iz-label" :for="'team-name-' + _uid">Name</label>
            <input :id="'team-name-' + _uid" v-model="draft.name" class="iz-input" type="text" required :disabled="saving">

            <label class="iz-label" :for="'team-description-' + _uid">Description</label>
            <textarea :id="'team-description-' + _uid" v-model="draft.description" class="iz-input" rows="2" :disabled="saving" />

            <div class="teams-panel__capacity-fields">
              <label class="iz-label" :for="'team-fte-' + _uid">
                FTE
                <input :id="'team-fte-' + _uid" v-model.number="draft.fte" class="iz-input" type="number" min="0" step="any" required :disabled="saving">
              </label>
              <label class="iz-label" :for="'team-projects-per-fte-' + _uid">
                Projects per FTE
                <input :id="'team-projects-per-fte-' + _uid" v-model.number="draft.projectsPerFte" class="iz-input" type="number" min="0.01" step="any" required :disabled="saving">
              </label>
            </div>
            <p class="teams-panel__capacity-preview">
              Capacity: <strong>{{ draftCapacity }}</strong> concurrent projects
            </p>

            <fieldset class="teams-panel__members">
              <legend class="iz-label">Members</legend>
              <label v-for="member in members" :key="memberUid(member)" class="teams-panel__member">
                <input v-model="selectedMembers" type="checkbox" :value="memberUid(member)" :disabled="saving">
                <span>{{ member.displayName || memberUid(member) }}</span>
                <small v-if="member.email">{{ member.email }}</small>
              </label>
              <span v-if="!members.length" class="iz-empty">Add organization members before assigning teams.</span>
            </fieldset>

            <p v-if="editorError" class="iz-error" role="alert">{{ editorError }}</p>
          </div>
          <footer class="iz-modal__footer">
            <button type="button" class="iz-btn iz-btn--sm" :disabled="saving" @click="closeEditor">Cancel</button>
            <button type="submit" class="iz-btn iz-btn--primary iz-btn--sm" :disabled="saving || !canSave">
              {{ saving ? "Saving..." : "Save team" }}
            </button>
          </footer>
        </form>
      </div>
    </div>

    <ConfirmDialog
      v-if="deleteTarget"
      :title="'Delete ' + deleteTarget.name + '?'"
      message="Team memberships and project assignments for this team will also be removed."
      confirm-label="Delete team"
      busy-label="Deleting..."
      :busy="deleting"
      :error="deleteError"
      danger
      @confirm="deleteTeam"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script>
import ConfirmDialog from "./ConfirmDialog.vue";
import {
  addTeamMember,
  assignProjectTeam,
  createOrganizationTeam,
  deleteOrganizationTeam,
  listOrganizationTeams,
  listProjectTeamAssignments,
  removeTeamMember,
  updateOrganizationTeam,
} from "../services/organizationApi";
import { addProjectTeamMembers } from "../services/projectCreatorApi";

export default {
  name: "TeamsPanel",
  components: { ConfirmDialog },
  props: {
    orgId: { type: Number, required: true },
    members: {
      type: Array,
      default: function () {
        return [];
      },
    },
    canManage: { type: Boolean, default: false },
  },
  data: function () {
    return {
      teams: [],
      assignments: [],
      loading: true,
      assignmentsLoading: true,
      error: "",
      assignmentsError: "",
      editorOpen: false,
      editorError: "",
      saving: false,
      draft: this.emptyDraft(),
      selectedMembers: [],
      deleteTarget: null,
      deleting: false,
      deleteError: "",
      assignmentBusy: {},
      assignmentErrors: {},
      assignmentSelections: {},
    };
  },
  watch: {
    members: function () {
      this.loadTeams();
    },
  },
  computed: {
    editing: function () {
      return this.draft.id !== null;
    },
    canSave: function () {
      return this.draft.name.trim() !== ""
        && Number.isFinite(Number(this.draft.fte))
        && Number(this.draft.fte) >= 0
        && Number.isFinite(Number(this.draft.projectsPerFte))
        && Number(this.draft.projectsPerFte) > 0;
    },
    draftCapacity: function () {
      if (!this.canSave) return "—";
      return this.formatNumber(Number(this.draft.fte) * Number(this.draft.projectsPerFte));
    },
    unassignedCount: function () {
      return this.assignments.filter(function (project) {
        return project.team === null;
      }).length;
    },
    teamCountLabel: function () {
      var label = this.teams.length === 1 ? " team" : " teams";
      return this.teams.length + label;
    },
  },
  mounted: function () {
    this.load();
  },
  methods: {
    emptyDraft: function () {
      return { id: null, name: "", description: "", fte: 1, projectsPerFte: 1 };
    },
    memberUid: function (member) {
      return member.userId || member.uid || "";
    },
    formatNumber: function (value) {
      return Number(value || 0).toLocaleString("en-US", { maximumFractionDigits: 2 });
    },
    initials: function (name) {
      return String(name || "T")
        .split(/\s+/)
        .slice(0, 2)
        .map(function (part) {
          return part.charAt(0);
        })
        .join("")
        .toUpperCase();
    },
    message: function (error, fallback) {
      return error && error.message ? error.message : fallback;
    },
    emitSummary: function () {
      this.$emit("summary", {
        teamCount: this.teams.length,
        unassignedCount: this.unassignedCount,
      });
    },
    syncAssignmentSelections: function () {
      var selections = {};
      this.assignments.forEach(function (project) {
        selections[project.projectId] = project.team ? String(project.team.id) : "";
      });
      this.assignmentSelections = selections;
    },
    assignmentValue: function (project) {
      var value = this.assignmentSelections[project.projectId];
      if (value !== undefined) return value;
      return project.team ? String(project.team.id) : "";
    },
    load: async function () {
      await Promise.all([this.loadTeams(), this.loadAssignments()]);
    },
    loadTeams: async function () {
      this.loading = true;
      this.error = "";
      try {
        this.teams = await listOrganizationTeams(this.orgId);
      } catch (error) {
        this.error = this.message(error, "Teams could not be loaded.");
      } finally {
        this.loading = false;
        this.emitSummary();
      }
    },
    loadAssignments: async function () {
      this.assignmentsLoading = true;
      this.assignmentsError = "";
      try {
        this.assignments = await listProjectTeamAssignments(this.orgId);
        this.syncAssignmentSelections();
      } catch (error) {
        this.assignmentsError = this.message(error, "Project assignments could not be loaded.");
      } finally {
        this.assignmentsLoading = false;
        this.emitSummary();
      }
    },
    openCreate: function () {
      this.draft = this.emptyDraft();
      this.selectedMembers = [];
      this.editorError = "";
      this.editorOpen = true;
    },
    openEdit: function (team) {
      this.draft = {
        id: team.id,
        name: team.name,
        description: team.description || "",
        fte: Number(team.fte),
        projectsPerFte: Number(team.projectsPerFte),
      };
      this.selectedMembers = (team.members || []).map(function (member) {
        return member.uid;
      });
      this.editorError = "";
      this.editorOpen = true;
    },
    closeEditor: function () {
      if (!this.saving) {
        this.editorOpen = false;
      }
    },
    saveTeam: async function () {
      if (!this.canSave || this.saving) return;
      this.saving = true;
      this.editorError = "";
      var teamSaved = false;
      try {
        var payload = {
          name: this.draft.name.trim(),
          description: this.draft.description.trim() || null,
          fte: Number(this.draft.fte),
          projectsPerFte: Number(this.draft.projectsPerFte),
        };
        var team;
        if (this.editing) {
          team = await updateOrganizationTeam(this.orgId, this.draft.id, payload);
        } else {
          team = await createOrganizationTeam(this.orgId, payload);
        }
        teamSaved = true;
        this.draft.id = team.id;
        var originalMembers = (team.members || []).map(function (member) {
          return member.uid;
        });
        var additions = this.selectedMembers.filter(function (uid) {
          return originalMembers.indexOf(uid) === -1;
        });
        var removals = originalMembers.filter(function (uid) {
          return this.selectedMembers.indexOf(uid) === -1;
        }, this);
        for (var i = 0; i < additions.length; i++) await addTeamMember(this.orgId, team.id, additions[i]);
        for (var j = 0; j < removals.length; j++) await removeTeamMember(this.orgId, team.id, removals[j]);
        this.editorOpen = false;
        await this.load();
        this.$emit("changed");
      } catch (error) {
        this.editorError = this.message(error, "The team could not be saved.");
        if (teamSaved) this.editorError += " Any changes completed before this error remain saved; retry to finish synchronizing members.";
      } finally {
        this.saving = false;
      }
    },
    openDelete: function (team) {
      this.deleteTarget = team;
      this.deleteError = "";
    },
    cancelDelete: function () {
      if (this.deleting) return;
      this.deleteTarget = null;
      this.deleteError = "";
    },
    deleteTeam: async function () {
      if (!this.deleteTarget || this.deleting) return;
      this.deleting = true;
      this.deleteError = "";
      try {
        await deleteOrganizationTeam(this.orgId, this.deleteTarget.id);
        this.deleteTarget = null;
        await this.load();
        this.$emit("changed");
      } catch (error) {
        this.deleteError = this.message(error, "The team could not be deleted.");
        await this.load();
        if (!this.teams.some(function (team) {
          return team.id === this.deleteTarget.id;
        }, this)) {
          this.deleteTarget = null;
        }
      } finally {
        this.deleting = false;
      }
    },
    saveAssignment: async function (project, event) {
      var select = event.target;
      var previous = this.assignmentValue(project);
      var teamId = select.value === "" ? null : Number(select.value);
      this.$set(this.assignmentSelections, project.projectId, select.value);
      this.$set(this.assignmentBusy, project.projectId, true);
      this.$delete(this.assignmentErrors, project.projectId);
      try {
        this.assignments = await assignProjectTeam(this.orgId, project.projectId, teamId);
        this.syncAssignmentSelections();
        this.emitSummary();
        if (teamId !== null) {
          try {
            await addProjectTeamMembers(project.projectId, teamId);
          } catch (error) {
            this.$set(
              this.assignmentErrors,
              project.projectId,
              this.message(error, "The team was assigned, but its members could not all be added to the project."),
            );
          }
        }
        this.$emit("changed");
      } catch (error) {
        this.$set(this.assignmentErrors, project.projectId, this.message(error, "The assignment could not be saved."));
        this.$set(this.assignmentSelections, project.projectId, previous);
        await this.loadAssignments();
      } finally {
        this.$set(this.assignmentBusy, project.projectId, false);
      }
    },
  },
};
</script>

<style scoped>
.teams-panel { display: grid; gap: var(--iz-gap); }
.teams-panel__toolbar,
.teams-panel__actions,
.teams-panel__facts,
.teams-panel__identity { display: flex; align-items: center; gap: var(--iz-gap-tight); }
.teams-panel__toolbar { justify-content: space-between; }
.teams-panel__count { color: var(--iz-text-secondary); font-size: var(--iz-fs-sm); }
.teams-panel__state { min-height: 84px; display: flex; align-items: center; justify-content: center; gap: var(--iz-gap-tight); }
.teams-panel__list, .teams-panel__assignment-list { display: grid; gap: var(--iz-gap-tight); margin: 0; padding: 0; list-style: none; }
.teams-panel__team { display: grid; grid-template-columns: minmax(180px, 1fr) minmax(260px, auto) auto; gap: var(--iz-gap); padding: var(--iz-pad-row); }
.teams-panel__identity { min-width: 0; }
.teams-panel__avatar { flex: 0 0 auto; }
.teams-panel__facts { flex-wrap: wrap; color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); }
.teams-panel__facts span + span { padding-left: var(--iz-gap-tight); border-left: 1px solid var(--iz-border); }
.teams-panel__actions { justify-self: end; }
.teams-panel__assignments { overflow: hidden; }
.teams-panel__assignments-header { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--iz-gap); padding: var(--iz-pad-panel); border-bottom: 1px solid var(--iz-border); }
.teams-panel__assignments-title { margin: 0; color: var(--iz-text); font-size: var(--iz-fs-md); }
.teams-panel__assignments-header p { margin: 3px 0 0; color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); }
.teams-panel__assignment-list { gap: 0; }
.teams-panel__assignment-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(180px, 280px) 64px; align-items: center; gap: var(--iz-gap-tight); padding: var(--iz-pad-row) var(--iz-pad-panel); border-bottom: 1px solid var(--iz-border); }
.teams-panel__assignment-row:last-child { border-bottom: 0; }
.teams-panel__project { display: grid; gap: 3px; min-width: 0; }
.teams-panel__assignment-error { color: var(--iz-danger-text); font-size: var(--iz-fs-xs); }
.teams-panel__assignment-select { width: auto; min-width: 0; }
.teams-panel__assignment-status { color: var(--iz-text-muted); font-size: var(--iz-fs-xs); }
.teams-panel__modal { width: min(640px, 100%); }
.teams-panel__modal h3 { margin: 0; font-size: var(--iz-fs-lg); }
.teams-panel__form { display: grid; gap: var(--iz-gap-tight); }
.teams-panel__capacity-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--iz-gap); }
.teams-panel__capacity-fields label { display: grid; gap: 4px; }
.teams-panel__capacity-preview { margin: 0; padding: var(--iz-pad-row); border-radius: var(--iz-radius); background: var(--iz-accent-bg); color: var(--iz-accent-bg-text); font-size: var(--iz-fs-sm); }
.teams-panel__members { display: grid; gap: var(--iz-gap-tight); max-height: 240px; margin: 0; padding: var(--iz-pad-card); overflow-y: auto; border: 1px solid var(--iz-border); border-radius: var(--iz-radius); }
.teams-panel__member { display: grid; grid-template-columns: auto minmax(0, 1fr) minmax(0, auto); align-items: center; gap: var(--iz-gap-tight); color: var(--iz-text); font-size: var(--iz-fs-sm); }
.teams-panel__member small { color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); overflow-wrap: anywhere; }
@media (max-width: 900px) {
  .teams-panel__team { grid-template-columns: minmax(0, 1fr) auto; }
  .teams-panel__facts { grid-column: 1 / -1; grid-row: 2; }
  .teams-panel__actions { grid-column: 2; grid-row: 1; }
}
@media (max-width: 620px) {
  .teams-panel__team { grid-template-columns: minmax(0, 1fr); }
  .teams-panel__actions { grid-column: 1; grid-row: auto; justify-self: start; }
  .teams-panel__facts { grid-column: 1; grid-row: auto; }
  .teams-panel__assignment-row { grid-template-columns: minmax(0, 1fr); }
  .teams-panel__assignment-status { min-height: 0; }
  .teams-panel__capacity-fields { grid-template-columns: minmax(0, 1fr); }
  .teams-panel__member { grid-template-columns: auto minmax(0, 1fr); }
  .teams-panel__member small { grid-column: 2; }
}
</style>
