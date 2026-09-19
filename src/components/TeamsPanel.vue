<template>
  <div class="teams-panel">
    <div class="teams-panel__toolbar">
      <div class="teams-panel__toolbar-left">
        <span class="teams-panel__count">{{ teamCountLabel }}</span>
        <button
          v-if="unassignedCount > 0"
          type="button"
          class="iz-pill iz-pill--warning teams-panel__unassigned-pill"
          title="Click to review and assign unassigned projects"
          @click="unassignedModalOpen = true"
        >
          {{ unassignedCount }} unassigned
        </button>
      </div>
      <button v-if="canManage" type="button" class="iz-btn iz-btn--primary iz-btn--sm" @click="openCreate">
        + New team
      </button>
    </div>

    <!-- ── Unassigned Projects Alert Banner ── -->
    <div v-if="unassignedCount > 0" class="teams-panel__unassigned-banner">
      <div class="teams-panel__unassigned-info">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="8" x2="12" y2="12" />
          <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
        <span>
          <strong>{{ unassignedCount }} unassigned {{ unassignedCount === 1 ? "project" : "projects" }}</strong>
          — assign responsible teams to enable accurate capacity and workload tracking.
        </span>
      </div>
      <button
        type="button"
        class="iz-btn iz-btn--warning iz-btn--sm"
        @click="unassignedModalOpen = true"
      >
        Review &amp; Assign ({{ unassignedCount }})
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
          <span class="teams-panel__load-badge" :class="'teams-panel__load--' + teamLoadStatus(team).tone">
            <strong>{{ teamProjects(team.id).length }} / {{ formatNumber(team.projectCapacity) }}</strong> projects
          </span>
          <span>{{ formatNumber(team.fte) }} FTE x {{ formatNumber(team.projectsPerFte) }}</span>
        </div>
        <div class="teams-panel__actions">
          <button
            type="button"
            class="iz-btn iz-btn--sm iz-btn--secondary teams-panel__projects-btn"
            title="View and manage projects assigned to this team"
            @click="openTeamProjects(team)"
          >
            Projects ({{ teamProjects(team.id).length }})
          </button>
          <button v-if="canManage" type="button" class="iz-btn iz-btn--sm" @click="openEdit(team)">Edit</button>
          <button v-if="canManage" type="button" class="iz-btn iz-btn--danger iz-btn--sm" @click="openDelete(team)">Delete</button>
        </div>
      </li>
    </ul>

    <!-- ── Team Projects Modal ── -->
    <div v-if="projectsModalTeam" class="iz-modal-backdrop" @click.self="closeProjectsModal">
      <div class="iz-modal teams-panel__modal teams-panel__projects-modal" role="dialog" aria-modal="true" :aria-labelledby="'team-projects-title-' + _uid">
        <header class="iz-modal__header">
          <div>
            <h3 :id="'team-projects-title-' + _uid">{{ projectsModalTeam.name }} — Projects</h3>
            <p class="teams-panel__modal-subtitle">
              Capacity: <strong>{{ teamProjects(projectsModalTeam.id).length }} / {{ formatNumber(projectsModalTeam.projectCapacity) }}</strong> projects
              <span class="iz-badge" :class="loadBadgeClass(projectsModalTeam)">{{ loadBadgeText(projectsModalTeam) }}</span>
            </p>
          </div>
          <button type="button" class="iz-close iz-close--sm" aria-label="Close" @click="closeProjectsModal">&times;</button>
        </header>

        <div class="iz-modal__body teams-panel__projects-body">
          <!-- Quick assign bar if unassigned projects exist -->
          <div v-if="canManage && unassignedProjects.length" class="teams-panel__quick-assign">
            <label class="iz-label" :for="'assign-to-team-' + _uid">Assign an unassigned project to {{ projectsModalTeam.name }}:</label>
            <div class="teams-panel__quick-assign-row">
              <select :id="'assign-to-team-' + _uid" class="iz-select iz-select--sm" v-model="projectToAssignId" :disabled="assigningQuick">
                <option value="" disabled>Select unassigned project...</option>
                <option v-for="p in unassignedProjects" :key="p.projectId" :value="p.projectId">{{ p.projectName }}</option>
              </select>
              <button
                type="button"
                class="iz-btn iz-btn--primary iz-btn--sm"
                :disabled="!projectToAssignId || assigningQuick"
                @click="assignSelectedProjectToTeam(projectsModalTeam.id)"
              >
                {{ assigningQuick ? "Assigning..." : "+ Assign to team" }}
              </button>
            </div>
          </div>

          <!-- List of currently assigned projects -->
          <h4 class="teams-panel__section-heading">Assigned projects ({{ teamProjects(projectsModalTeam.id).length }})</h4>
          <div v-if="!teamProjects(projectsModalTeam.id).length" class="iz-empty teams-panel__empty-projects">
            No projects assigned to {{ projectsModalTeam.name }} yet.
          </div>
          <ul v-else class="teams-panel__project-modal-list">
            <li v-for="p in teamProjects(projectsModalTeam.id)" :key="p.projectId" class="teams-panel__project-modal-item">
              <div class="teams-panel__project-info">
                <span class="teams-panel__project-bullet">&bull;</span>
                <strong>{{ p.projectName }}</strong>
                <small v-if="p.updatedAt" class="teams-panel__project-date">Assigned {{ formatDate(p.updatedAt) }}</small>
                <small v-if="assignmentErrors[p.projectId]" class="teams-panel__assignment-error" role="alert">
                  {{ assignmentErrors[p.projectId] }}
                </small>
              </div>
              <div v-if="canManage" class="teams-panel__project-item-actions">
                <button
                  type="button"
                  class="iz-btn iz-btn--danger-quiet iz-btn--sm"
                  :disabled="assignmentBusy[p.projectId]"
                  title="Unassign project from this team"
                  @click="unassignProject(p.projectId)"
                >
                  {{ assignmentBusy[p.projectId] ? "..." : "Remove" }}
                </button>
              </div>
            </li>
          </ul>
        </div>

        <footer class="iz-modal__footer">
          <button type="button" class="iz-btn iz-btn--sm" @click="closeProjectsModal">Done</button>
        </footer>
      </div>
    </div>

    <!-- ── Unassigned Projects Modal ── -->
    <div v-if="unassignedModalOpen" class="iz-modal-backdrop" @click.self="unassignedModalOpen = false">
      <div class="iz-modal teams-panel__modal teams-panel__unassigned-modal" role="dialog" aria-modal="true" :aria-labelledby="'unassigned-modal-title-' + _uid">
        <header class="iz-modal__header">
          <div>
            <h3 :id="'unassigned-modal-title-' + _uid">Unassigned Projects ({{ unassignedProjects.length }})</h3>
            <p class="teams-panel__modal-subtitle">Assign each project to a responsible team to balance workload capacity.</p>
          </div>
          <button type="button" class="iz-close iz-close--sm" aria-label="Close" @click="unassignedModalOpen = false">&times;</button>
        </header>

        <div class="iz-modal__body">
          <div v-if="unassignedProjects.length > 5" class="teams-panel__search-box">
            <input
              type="search"
              v-model="unassignedSearchQuery"
              class="iz-input iz-input--sm"
              placeholder="Search unassigned projects..."
            />
          </div>
          <div v-if="!filteredUnassignedProjects.length" class="iz-empty">
            {{ unassignedProjects.length === 0 ? "All projects are currently assigned to a team!" : "No unassigned projects match your search." }}
          </div>
          <ul v-else class="teams-panel__unassigned-list">
            <li v-for="p in filteredUnassignedProjects" :key="p.projectId" class="teams-panel__unassigned-item">
              <span class="teams-panel__project-name">
                <strong>{{ p.projectName }}</strong>
                <small v-if="assignmentErrors[p.projectId]" class="teams-panel__assignment-error" role="alert">
                  {{ assignmentErrors[p.projectId] }}
                </small>
              </span>
              <div class="teams-panel__unassigned-assign-action">
                <select
                  class="iz-select iz-select--sm"
                  :disabled="!canManage || assignmentBusy[p.projectId] || !teams.length"
                  :aria-label="'Select team for ' + p.projectName"
                  @change="handleUnassignedSelect(p, $event)"
                >
                  <option value="" selected disabled>Select team...</option>
                  <option v-for="t in teams" :key="t.id" :value="t.id">
                    {{ t.name }} ({{ teamProjects(t.id).length }}/{{ formatNumber(t.projectCapacity) }})
                  </option>
                </select>
                <span class="teams-panel__assignment-status" aria-live="polite">
                  {{ assignmentBusy[p.projectId] ? "Saving..." : "" }}
                </span>
              </div>
            </li>
          </ul>
        </div>

        <footer class="iz-modal__footer">
          <button type="button" class="iz-btn iz-btn--sm" @click="unassignedModalOpen = false">Done</button>
        </footer>
      </div>
    </div>

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
      projectsModalTeam: null,
      unassignedModalOpen: false,
      projectToAssignId: "",
      assigningQuick: false,
      unassignedSearchQuery: "",
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
    unassignedProjects: function () {
      return this.assignments.filter(function (project) {
        return project.team === null;
      });
    },
    filteredUnassignedProjects: function () {
      var query = (this.unassignedSearchQuery || "").trim().toLowerCase();
      if (!query) return this.unassignedProjects;
      return this.unassignedProjects.filter(function (p) {
        return (p.projectName || "").toLowerCase().indexOf(query) !== -1;
      });
    },
    unassignedCount: function () {
      return this.unassignedProjects.length;
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
    teamProjects: function (teamId) {
      return this.assignments.filter(function (p) {
        return p.team && p.team.id === teamId;
      });
    },
    teamLoadStatus: function (team) {
      if (!team) return { tone: "neutral", text: "0 assigned" };
      var count = this.teamProjects(team.id).length;
      var cap = Number(team.projectCapacity || 0);
      if (cap <= 0) return { tone: "neutral", text: count + " assigned" };
      if (count > cap) return { tone: "danger", text: count + " / " + this.formatNumber(cap) + " (Over capacity)" };
      if (count === cap) return { tone: "warning", text: count + " / " + this.formatNumber(cap) + " (At capacity)" };
      return { tone: "success", text: count + " / " + this.formatNumber(cap) + " (" + (cap - count) + " available)" };
    },
    loadBadgeClass: function (team) {
      var status = this.teamLoadStatus(team);
      if (status.tone === "danger") return "iz-badge--danger";
      if (status.tone === "warning") return "iz-badge--warning";
      if (status.tone === "success") return "iz-badge--success";
      return "";
    },
    loadBadgeText: function (team) {
      return this.teamLoadStatus(team).text;
    },
    openTeamProjects: function (team) {
      this.projectsModalTeam = team;
      this.projectToAssignId = "";
    },
    closeProjectsModal: function () {
      this.projectsModalTeam = null;
      this.projectToAssignId = "";
    },
    assignSelectedProjectToTeam: async function (teamId) {
      if (!this.projectToAssignId) return;
      this.assigningQuick = true;
      var projectId = Number(this.projectToAssignId);
      try {
        await this.executeAssignment(projectId, teamId);
        this.projectToAssignId = "";
      } finally {
        this.assigningQuick = false;
      }
    },
    unassignProject: async function (projectId) {
      await this.executeAssignment(projectId, null);
    },
    handleUnassignedSelect: async function (project, event) {
      var targetTeamId = event.target.value === "" ? null : Number(event.target.value);
      if (targetTeamId === null) return;
      await this.executeAssignment(project.projectId, targetTeamId);
    },
    executeAssignment: async function (projectId, teamId) {
      this.$set(this.assignmentBusy, projectId, true);
      this.$delete(this.assignmentErrors, projectId);
      try {
        this.assignments = await assignProjectTeam(this.orgId, projectId, teamId);
        this.syncAssignmentSelections();
        this.emitSummary();
        if (teamId !== null) {
          try {
            await addProjectTeamMembers(projectId, teamId);
          } catch (error) {
            this.$set(
              this.assignmentErrors,
              projectId,
              this.message(error, "The team was assigned, but its members could not all be added to the project."),
            );
          }
        }
        this.$emit("changed");
      } catch (error) {
        this.$set(this.assignmentErrors, projectId, this.message(error, "The assignment could not be saved."));
        await this.loadAssignments();
      } finally {
        this.$set(this.assignmentBusy, projectId, false);
      }
    },
    saveAssignment: async function (project, event) {
      var select = event.target;
      var teamId = select.value === "" ? null : Number(select.value);
      await this.executeAssignment(project.projectId, teamId);
    },
    formatDate: function (dateStr) {
      if (!dateStr) return "";
      try {
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
      } catch (e) {
        return dateStr;
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
.teams-panel__identity,
.teams-panel__toolbar-left { display: flex; align-items: center; gap: var(--iz-gap-tight); }
.teams-panel__toolbar { justify-content: space-between; }
.teams-panel__count { color: var(--iz-text-secondary); font-size: var(--iz-fs-sm); }
.teams-panel__unassigned-pill { cursor: pointer; border: 0; }
.teams-panel__unassigned-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--iz-gap);
  padding: 12px 16px;
  border: 1px solid var(--iz-warning);
  border-radius: var(--iz-radius);
  background: color-mix(in srgb, var(--iz-warning) 12%, var(--iz-surface));
}
.teams-panel__unassigned-info {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--iz-text);
  font-size: var(--iz-fs-sm);
}
.teams-panel__unassigned-info svg {
  width: 20px;
  height: 20px;
  flex: 0 0 20px;
  color: var(--iz-warning);
}
.teams-panel__state { min-height: 84px; display: flex; align-items: center; justify-content: center; gap: var(--iz-gap-tight); }
.teams-panel__list { display: grid; gap: var(--iz-gap-tight); margin: 0; padding: 0; list-style: none; }
.teams-panel__team { display: grid; grid-template-columns: minmax(180px, 1fr) minmax(280px, auto) auto; gap: var(--iz-gap); padding: var(--iz-pad-row); }
.teams-panel__identity { min-width: 0; }
.teams-panel__avatar { flex: 0 0 auto; }
.teams-panel__facts { flex-wrap: wrap; color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); }
.teams-panel__facts span + span { padding-left: var(--iz-gap-tight); border-left: 1px solid var(--iz-border); }
.teams-panel__load-badge strong { color: var(--iz-text); }
.teams-panel__load--success { color: var(--iz-success); }
.teams-panel__load--warning { color: var(--iz-warning); font-weight: 600; }
.teams-panel__load--danger { color: var(--iz-danger); font-weight: 700; }
.teams-panel__actions { justify-self: end; }
.teams-panel__projects-btn { font-weight: 600; }
.teams-panel__modal { width: min(640px, 100%); }
.teams-panel__projects-modal,
.teams-panel__unassigned-modal { width: min(720px, 100%); }
.teams-panel__modal-subtitle { margin: 4px 0 0; color: var(--iz-text-secondary); font-size: var(--iz-fs-xs); display: flex; align-items: center; gap: 8px; }
.teams-panel__quick-assign {
  padding: 12px;
  border: 1px solid var(--iz-border);
  border-radius: var(--iz-radius);
  background: var(--iz-surface-subtle);
  margin-bottom: var(--iz-gap);
  display: grid;
  gap: 6px;
}
.teams-panel__quick-assign-row { display: flex; align-items: center; gap: var(--iz-gap-tight); }
.teams-panel__quick-assign-row .iz-select { flex: 1 1 auto; }
.teams-panel__section-heading { margin: 0 0 8px; color: var(--iz-text); font-size: var(--iz-fs-sm); font-weight: 700; }
.teams-panel__empty-projects { padding: var(--iz-pad-card); text-align: center; }
.teams-panel__project-modal-list,
.teams-panel__unassigned-list {
  display: grid;
  gap: 0;
  border: 1px solid var(--iz-border);
  border-radius: var(--iz-radius);
  overflow: hidden;
  list-style: none;
  margin: 0;
  padding: 0;
  background: var(--iz-surface);
}
.teams-panel__project-modal-item,
.teams-panel__unassigned-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--iz-gap);
  padding: 10px 14px;
  border-bottom: 1px solid var(--iz-border);
}
.teams-panel__project-modal-item:last-child,
.teams-panel__unassigned-item:last-child { border-bottom: 0; }
.teams-panel__project-info,
.teams-panel__project-name { display: flex; align-items: center; gap: 8px; min-width: 0; flex-wrap: wrap; }
.teams-panel__project-bullet { color: var(--iz-accent); font-size: 16px; }
.teams-panel__project-date { color: var(--iz-text-muted); font-size: var(--iz-fs-xs); }
.teams-panel__project-item-actions,
.teams-panel__unassigned-assign-action { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.teams-panel__search-box { margin-bottom: var(--iz-gap-tight); }
.teams-panel__search-box input { width: 100%; }
.teams-panel__assignment-status { color: var(--iz-text-muted); font-size: var(--iz-fs-xs); }
.teams-panel__assignment-error { color: var(--iz-danger-text); font-size: var(--iz-fs-xs); width: 100%; }
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
  .teams-panel__unassigned-banner { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 620px) {
  .teams-panel__team { grid-template-columns: minmax(0, 1fr); }
  .teams-panel__actions { grid-column: 1; grid-row: auto; justify-self: start; }
  .teams-panel__facts { grid-column: 1; grid-row: auto; }
  .teams-panel__project-modal-item,
  .teams-panel__unassigned-item { flex-direction: column; align-items: flex-start; }
  .teams-panel__project-item-actions,
  .teams-panel__unassigned-assign-action { width: 100%; justify-content: space-between; }
  .teams-panel__capacity-fields { grid-template-columns: minmax(0, 1fr); }
  .teams-panel__member { grid-template-columns: auto minmax(0, 1fr); }
  .teams-panel__member small { grid-column: 2; }
}
</style>
