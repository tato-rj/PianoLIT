import { bootGame } from "../duel/DuelClient.js";
import { ToneTrek } from "./tonetrek/ToneTrek.js";

const options = window.__challengeOptions || {};

bootGame((duelOptions) => new ToneTrek({
  ...(duelOptions || options),
}));
